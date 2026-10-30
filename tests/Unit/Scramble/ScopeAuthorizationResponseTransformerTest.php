<?php

use Dedoc\Scramble\Infer;
use Illuminate\Routing\Route;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Reference;
use Illuminate\Auth\Access\AuthorizationException;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use ChiefTools\SDK\Scramble\ScopeAuthorizationResponseTransformer;
use Dedoc\Scramble\Support\ExceptionToResponseExtensions\AuthorizationExceptionToResponseExtension;

it('documents a forbidden response for routes with scope middleware', function () {
    $route = new Route(['POST'], '/sample-resource', static fn (): null => null);
    $route->middleware(['api', 'scope:catalog:write']);

    $operation = Operation::make('post');

    (new ScopeAuthorizationResponseTransformer(scopeAuthorizationTypeTransformer()))->handle($operation, new RouteInfo($route, 'post'));

    expect(scopeAuthorizationResponseCodes($operation))->toBe([403]);
});

it('does not duplicate an already documented forbidden response', function () {
    $route = new Route(['POST'], '/sample-resource', static fn (): null => null);
    $route->middleware(['scope:catalog:write']);

    $typeTransformer = scopeAuthorizationTypeTransformer();

    $operation = Operation::make('post');
    $operation->addResponse($typeTransformer->toResponse(new ObjectType(AuthorizationException::class)));

    (new ScopeAuthorizationResponseTransformer($typeTransformer))->handle($operation, new RouteInfo($route, 'post'));

    expect(scopeAuthorizationResponseCodes($operation))->toBe([403]);
});

it('does not document a forbidden response without scope middleware', function () {
    $route = new Route(['POST'], '/sample-resource', static fn (): null => null);
    $route->middleware(['api', 'throttle:60,1']);

    $operation = Operation::make('post');

    (new ScopeAuthorizationResponseTransformer(scopeAuthorizationTypeTransformer()))->handle($operation, new RouteInfo($route, 'post'));

    expect($operation->responses)->toBe([]);
});

function scopeAuthorizationTypeTransformer(): TypeTransformer
{
    return new TypeTransformer(
        app(Infer::class),
        new OpenApiContext(OpenApi::make('3.1.0'), new GeneratorConfig('test')),
        exceptionToResponseExtensionsClasses: [AuthorizationExceptionToResponseExtension::class],
    );
}

/** @return list<int> */
function scopeAuthorizationResponseCodes(Operation $operation): array
{
    return array_map(
        static fn (Reference|Response $response): int => ($response instanceof Reference ? $response->resolve() : $response)->code,
        $operation->responses ?? [],
    );
}
