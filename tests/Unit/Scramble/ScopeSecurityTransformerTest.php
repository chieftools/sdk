<?php

use Illuminate\Routing\Route;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Generator\Operation;
use ChiefTools\SDK\Scramble\ScopeSecurityTransformer;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use ChiefTools\SDK\Scramble\Contracts\ProvidesSecurityRequirements;

it('converts a route scope to security requirements', function () {
    $route = new Route(['GET'], '/sample-resource', static fn (): null => null);
    $route->middleware(['api', 'scope:catalog:read']);

    $operation = Operation::make('get');

    (new ScopeSecurityTransformer)->handle($operation, new RouteInfo($route, 'get'));

    expect(scopeSecurityRequirements($operation))->toBe([
        ['openid' => ['catalog:read']],
        ['ctp' => ['catalog:read']],
        ['ctt' => ['catalog:read']],
    ]);
});

it('documents comma separated scopes as alternatives', function () {
    $route = new Route(['GET'], '/sample-resource', static fn (): null => null);
    $route->middleware(['scope:catalog:read,invoices:write']);

    $operation = Operation::make('get');

    (new ScopeSecurityTransformer)->handle($operation, new RouteInfo($route, 'get'));

    expect(scopeSecurityRequirements($operation))->toBe([
        ['openid' => ['catalog:read']],
        ['openid' => ['invoices:write']],
        ['ctp' => ['catalog:read']],
        ['ctp' => ['invoices:write']],
        ['ctt' => ['catalog:read']],
        ['ctt' => ['invoices:write']],
    ]);
});

it('documents separate scope middleware as combined requirements', function () {
    $route = new Route(['GET'], '/sample-resource', static fn (): null => null);
    $route->middleware([
        'scope:catalog:read,invoices:read',
        'scope:organization:read',
    ]);

    $operation = Operation::make('get');

    (new ScopeSecurityTransformer)->handle($operation, new RouteInfo($route, 'get'));

    expect(scopeSecurityRequirements($operation))->toBe([
        ['openid' => ['catalog:read', 'organization:read']],
        ['openid' => ['invoices:read', 'organization:read']],
        ['ctp' => ['catalog:read', 'organization:read']],
        ['ctp' => ['invoices:read', 'organization:read']],
        ['ctt' => ['catalog:read', 'organization:read']],
        ['ctt' => ['invoices:read', 'organization:read']],
    ]);
});

it('uses requirements provided by custom middleware', function () {
    $route = new Route(['GET'], '/sample-resource', static fn (): null => null);
    $route->middleware([
        TestSecurityRequirementsMiddleware::class,
        'scope:ignored:read',
    ]);

    $operation = Operation::make('get');

    (new ScopeSecurityTransformer)->handle($operation, new RouteInfo($route, 'get'));

    expect(scopeSecurityRequirements($operation))->toBe([
        ['openid' => ['profile:read']],
        ['ctp' => ['profile:read']],
        ['ctt' => []],
    ]);
});

it('does not add security requirements without scope middleware', function () {
    $route = new Route(['GET'], '/sample-resource', static fn (): null => null);
    $route->middleware(['api', 'throttle:60,1']);

    $operation = Operation::make('get');

    (new ScopeSecurityTransformer)->handle($operation, new RouteInfo($route, 'get'));

    expect($operation->security)->toBeNull();
});

/** @return list<array<string, list<string>>> */
function scopeSecurityRequirements(Operation $operation): array
{
    return array_map(
        static fn (SecurityRequirement $requirement): array => $requirement->toArray(),
        $operation->security ?? [],
    );
}

final class TestSecurityRequirementsMiddleware implements ProvidesSecurityRequirements
{
    public static function securityRequirements(): array
    {
        return [
            ['openid' => ['profile:read']],
            ['ctp' => ['profile:read']],
            ['ctt' => []],
        ];
    }
}
