<?php

namespace ChiefTools\SDK\Http\Controllers\API;

use GraphQL\Type\Schema;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GraphQL\Type\Introspection;
use GraphQL\Utils\SchemaPrinter;
use Laragraph\Utils\RequestParser;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\InterfaceType;
use Nuwave\Lighthouse\Schema\SchemaBuilder;
use Nuwave\Lighthouse\GraphQL as Lighthouse;
use Nuwave\Lighthouse\Http\GraphQLController;
use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use Nuwave\Lighthouse\Support\Contracts\CreatesResponse;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class GraphQL extends GraphQLController
{
    public function __invoke(
        Request $request,
        Lighthouse $graphQL,
        EventsDispatcher $eventsDispatcher,
        RequestParser $requestParser,
        CreatesResponse $createsResponse,
        CreatesContext $createsContext,
    ): SymfonyResponse {
        sync_user_timezone();

        $this->maybeExportForLocal($graphQL);

        return parent::__invoke($request, $graphQL, $eventsDispatcher, $requestParser, $createsResponse, $createsContext);
    }

    public function schema(SchemaBuilder $schemaBuilder): Response
    {
        $schema = SchemaPrinter::doPrint($schemaBuilder->schema());

        $appId = config('chief.id', 'app');

        return response()->make($schema, 200, [
            'Content-Type'        => 'text/plain',
            'Content-Disposition' => "inline; filename=\"{$appId}-schema.graphql\"",
        ]);
    }

    public function discovery(): array
    {
        $name  = config('app.name');
        $appId = config('chief.id');

        return [
            'title'          => $name,
            'description'    => "The {$name} GraphQL API.",
            'favicon_url'    => static_asset("icons/{$appId}_favicon.svg"),
            'logo_light_url' => static_asset("icons/{$appId}.svg"),
            'logo_dark_url'  => static_asset("icons/{$appId}_white.svg"),
        ];
    }

    private function clearASTCache(Lighthouse $graphQL): void
    {
        /** @var \Nuwave\Lighthouse\Schema\SchemaBuilder $schemaBuilder */
        $schemaBuilder = __access_class_property($graphQL, 'schemaBuilder');

        /** @var \Nuwave\Lighthouse\Schema\AST\ASTBuilder $astBuilder */
        $astBuilder = __access_class_property($schemaBuilder, 'astBuilder');

        /** @var \Nuwave\Lighthouse\Schema\AST\ASTCache $astCache */
        $astCache = __access_class_property($astBuilder, 'astCache');

        $astCache->clear();
    }

    private function maybeExportForLocal(Lighthouse $graphQL): void
    {
        // If we are in a local environment we print the schema and possible types configuration
        // on every request it's a bit wasteful but the impact is not that big and it saves
        // setting up git hooks and all that horrible jazz. For apollo and GitHub diffs.
        if (!app()->isLocal()) {
            return;
        }

        $fileList = shell_exec(sprintf("zsh -c '/sbin/md5 %s/**/*.graphql | /sbin/md5'", base_path('routes/graphql')));

        $cachedFileList = cache()->get('__dev__:graphql:fileList');

        if ($fileList === $cachedFileList) {
            return;
        }

        $this->clearASTCache($graphQL);

        $schema = app(SchemaBuilder::class)->schema();

        $controllerName = self::class;

        $fragmentTypes = /** @lang JavaScript */
            <<<JSEXPORT
                /** Generated by \{$controllerName} **/
                const fragmentTypes = JSON.parse('{$this->extractFragmentTypesJSON($schema)}');
                export default fragmentTypes;
                JSEXPORT;

        // Prevent constantly updating the fragmentTypes.js causing `yarn run watch` to work on every API request
        if (!file_exists($fragmentTypesPath = resource_path('js/api/possibleTypes.js')) || file_get_contents($fragmentTypesPath) !== $fragmentTypes) {
            file_put_contents($fragmentTypesPath, $fragmentTypes);
        }

        $exportedSchema = SchemaPrinter::doPrint($schema);

        // Prevent constantly updating the schema.public.graphql causing reindex work on every API request
        if (!file_exists($exportedSchemaFilePath = base_path('resources/graphql/schema.public.graphql')) || file_get_contents($exportedSchemaFilePath) !== $exportedSchema) {
            file_put_contents($exportedSchemaFilePath, $exportedSchema);
        }

        $introspectionSchema = json_encode(Introspection::fromSchema($schema));

        // Prevent constantly updating the schema.json causing reindex work on every API request
        if (!file_exists($introspectionSchemaFilePath = base_path('resources/graphql/schema.public.json')) || file_get_contents($introspectionSchemaFilePath) !== $introspectionSchema) {
            file_put_contents($introspectionSchemaFilePath, $introspectionSchema);
        }

        cache()->put('__dev__:graphql:fileList', $fileList, now()->addHour());
    }

    private function extractFragmentTypesJSON(Schema $schema): string
    {
        $possibleTypes = [];

        foreach ($schema->getTypeMap() as $name => $type) {
            if ($type instanceof UnionType || $type instanceof InterfaceType) {
                $possibleTypeNames = [];

                foreach ($schema->getPossibleTypes($type) as $possibleType) {
                    $possibleTypeNames[$possibleType->name] = [
                        'name' => $possibleType->name,
                    ];
                }

                if (!empty($possibleTypeNames)) {
                    $possibleTypes[$name] = [
                        'kind'          => strtoupper(str_replace_last('Type', '', class_basename($type))),
                        'name'          => $name,
                        'possibleTypes' => array_values($possibleTypeNames),
                    ];
                }
            }
        }

        return js_json_encode([
            '__schema' => [
                'types' => array_values($possibleTypes),
            ],
        ]);
    }
}
