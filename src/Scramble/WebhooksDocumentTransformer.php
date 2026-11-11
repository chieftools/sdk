<?php

namespace ChiefTools\SDK\Scramble;

use ReflectionEnum;
use Illuminate\Support\Str;
use ReflectionEnumBackedCase;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\PhpDoc;
use Dedoc\Scramble\Support\Generator\Path;
use Dedoc\Scramble\PhpDoc\PhpDocTypeHelper;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Types\Type;
use Dedoc\Scramble\Contracts\DocumentTransformer;
use ChiefTools\SDK\Webhook\Outbound\WebhookConfig;
use Dedoc\Scramble\Infer\Services\FileNameResolver;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use ChiefTools\SDK\Webhook\Outbound\Enums\BuiltInEvent;
use Dedoc\Scramble\Support\Generator\RequestBodyObject;
use ChiefTools\SDK\Webhook\Outbound\Attributes\WebhookData;
use ChiefTools\SDK\Webhook\Outbound\Attributes\WebhookObject;
use ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType;
use Dedoc\Scramble\Support\Type\ObjectType as InferredObjectType;

/**
 * Documents the app's webhook events in its OpenAPI document: every case of the `chief.webhooks.events` enum, and the
 * built-in test event, becomes a webhook whose request body is a schema named after the event, like
 * `ServerOfflineEvent`. Renderers that only list webhooks can still show each payload on their schemas page.
 *
 * `data.object` and the other `data` fields come from the `WebhookObject` and `WebhookData` attributes on the cases,
 * so an object that is an API resource gets the same schema as the API returns.
 *
 * Register it on an API with `->withDocumentTransformers(WebhooksDocumentTransformer::class)`.
 */
class WebhooksDocumentTransformer implements DocumentTransformer
{
    private const string MEDIA_TYPE = 'application/json';

    public function __construct(
        private readonly TypeTransformer $transformer,
    ) {}

    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $enum = WebhookConfig::eventsEnum();

        if (!WebhookConfig::enabled() || $enum === null) {
            return;
        }

        $headers = $this->headerParameters($document);

        foreach ([...$enum::cases(), ...BuiltInEvent::cases()] as $event) {
            $schema = $document->components->addSchema($this->schemaName($event), Schema::fromType($this->eventType($event))->setTitle($this->schemaName($event)));

            $operation = Operation::make('post')
                ->summary($event->description())
                ->setOperationId('webhook.' . $event->value)
                ->setTags([$event->group()])
                ->addParameters($headers)
                ->addRequestBodyObject(RequestBodyObject::make()->required()->setContent(self::MEDIA_TYPE, $schema))
                ->addResponse(Response::make('2XX')->setDescription('Any `2xx` status within 15 seconds acknowledges the delivery. Any other status, a timeout or a redirect fails it, and it is retried.'));

            $document->addWebhook($event->value, Path::make($event->value)->addOperation($operation));
        }
    }

    /** The payload's schema name, like `ServerOfflineEvent` for `server.offline`. */
    private function schemaName(WebhookEventType $event): string
    {
        return Str::studly(str_replace('.', '_', $event->value)) . 'Event';
    }

    private function eventType(WebhookEventType $event): ObjectType
    {
        $case = new ReflectionEnumBackedCase($event::class, $event->name);

        $data = (new ObjectType)
            ->addProperty('object', $this->objectType($case))
            ->setRequired(['object']);

        foreach ($case->getAttributes(WebhookData::class) as $attribute) {
            $field = $attribute->newInstance();

            $data->addProperty($field->name, $this->type($field->type, $case)->setDescription($field->description));

            if ($field->required) {
                $data->addRequired([$field->name]);
            }
        }

        return (new ObjectType)
            ->addProperty('id', (new StringType)->setDescription('Identifies the event, the same as the `webhook-id` header. It stays the same on retries and resends, use it to skip events you processed.')->example(WebhookConfig::idPrefix('event') . '01m6v3f5aydrktmzrbpz1pqh7e'))
            ->addProperty('type', (new StringType)->enum([$event->value])->setDescription($event->description()))
            ->addProperty('timestamp', (new StringType)->format('date-time')->setDescription('When the event happened, in UTC. It does not change on retries.'))
            ->addProperty('api_version', (new StringType)->setDescription('The version of the payload format.')->example(WebhookConfig::apiVersion()))
            ->addProperty('data', $data)
            ->setRequired(['id', 'type', 'timestamp', 'api_version', 'data']);
    }

    /** The object's schema from the case's `WebhookObject`, falling back to the enum's for what the case leaves out. */
    private function objectType(ReflectionEnumBackedCase $case): Type
    {
        $caseObject = ($case->getAttributes(WebhookObject::class)[0] ?? null)?->newInstance();
        $enumObject = ((new ReflectionEnum($case->class))->getAttributes(WebhookObject::class)[0] ?? null)?->newInstance();

        $type = $caseObject->type ?? $enumObject->type ?? null;

        return ($type === null ? new ObjectType : $this->type($type, $case))
            ->setDescription($caseObject->description ?? $enumObject->description ?? WebhookObject::DEFAULT_DESCRIPTION);
    }

    /** The schema of a class, or of a PHPDoc type with class names resolved like in the enum's file. */
    private function type(string $type, ReflectionEnumBackedCase $case): Type
    {
        if (class_exists($type) || interface_exists($type)) {
            $inferred = new InferredObjectType(ltrim($type, '\\'));
        } else {
            $file     = (new ReflectionEnum($case->class))->getFileName();
            $resolver = $file === false ? null : FileNameResolver::createForFile($file);
            $inferred = PhpDocTypeHelper::toType(PhpDoc::parse("/** @var {$type} */", $resolver)->getVarTagValues()[0]->type ?? new IdentifierTypeNode('mixed'));
        }

        $schema = $this->transformer->transform($inferred);

        // References are shared, a description belongs to this use only
        return $schema instanceof Reference ? clone $schema : $schema;
    }

    /** @return list<\Dedoc\Scramble\Support\Generator\Reference> */
    private function headerParameters(OpenApi $document): array
    {
        $parameters = [
            'webhook-id'        => 'The event\'s `id`, the same on every attempt.',
            'webhook-timestamp' => 'The Unix time this attempt was sent, reject requests more than five minutes from your clock.',
            'webhook-signature' => 'Space-separated `v1,{base64}` HMAC-SHA256 signatures of `{webhook-id}.{webhook-timestamp}.{body}`, following Standard Webhooks. Two while a rotated secret is still valid.',
        ];

        $references = [];

        foreach ($parameters as $name => $description) {
            $references[] = $document->components->addParameter(
                Str::studly(str_replace('-', '_', $name)),
                Parameter::make($name, 'header')->required(true)->setSchema(Schema::fromType(new StringType))->description($description),
            );
        }

        return $references;
    }
}
