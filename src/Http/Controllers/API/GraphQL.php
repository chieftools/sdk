<?php

namespace IronGate\Chief\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GraphQL\Utils\SchemaPrinter;
use Laragraph\Utils\RequestParser;
use Nuwave\Lighthouse\Schema\SchemaBuilder;
use Nuwave\Lighthouse\GraphQL as Lighthouse;
use GraphQL\Validator\Rules\DisableIntrospection;
use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use Nuwave\Lighthouse\Support\Contracts\CreatesResponse;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Nuwave\Lighthouse\Support\Http\Controllers\GraphQLController;
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

        config([
            'lighthouse.security.disable_introspection' => auth()->check()
                ? DisableIntrospection::DISABLED
                : DisableIntrospection::ENABLED,
        ]);

        // If we are in a local environment we print the schema on every request
        // it's a bit wasteful but the impact is not that big and it saves using git hooks
        if (app()->environment('local')) {
            $schema = app(SchemaBuilder::class)->schema();

            file_put_contents(
                base_path('routes/graphql/exported/schema.public.graphql'),
                SchemaPrinter::doPrint($schema)
            );

            config(['lighthouse.security.disable_introspection' => DisableIntrospection::DISABLED]);
        }

        return parent::__invoke($request, $graphQL, $eventsDispatcher, $requestParser, $createsResponse, $createsContext);
    }

    /**
     * Respond with the full GraphQL schema file.
     */
    public function schema(SchemaBuilder $schemaBuilder): Response
    {
        $schema = SchemaPrinter::doPrint($schemaBuilder->schema());

        $appId = config('chief.id', 'app');

        return response()->make($schema, 200, [
            'Content-Type'        => 'text/plain',
            'Content-Disposition' => "inline; filename=\"{$appId}-schema.graphql\"",
        ]);
    }
}
