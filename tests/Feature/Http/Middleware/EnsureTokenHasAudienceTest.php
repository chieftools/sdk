<?php

use Illuminate\Http\Request;
use ChiefTools\SDK\Entities\User;
use Symfony\Component\HttpFoundation\Response;
use ChiefTools\SDK\Auth\ChiefRemoteAccessToken;
use ChiefTools\SDK\Http\Middleware\EnsureTokenHasAudience;

it('registers the audience middleware alias', function () {
    expect(app('router')->getMiddleware()['audience'])->toBe(EnsureTokenHasAudience::class);
});

it('allows a remote token for the configured audience', function () {
    config()->set('chief.auth.audiences.mcp', 'https://resource.service.invalid/mcp');

    $request = audienceMiddlewareRequest('https://resource.service.invalid/mcp');

    $response = app(EnsureTokenHasAudience::class)->handle(
        $request,
        static fn (): Response => new Response('allowed'),
        'mcp',
    );

    expect($response->getStatusCode())->toBe(200);
});

it('returns 401 when the remote token has no audience', function () {
    config()->set('chief.auth.audiences.mcp', 'https://resource.service.invalid/mcp');

    $response = app(EnsureTokenHasAudience::class)->handle(
        audienceMiddlewareRequest(),
        static fn (): Response => new Response('allowed'),
        'mcp',
    );

    expect($response->getStatusCode())->toBe(401)
        ->and($response->headers->get('WWW-Authenticate'))->toBe('Bearer error="invalid_token"');
});

it('returns 401 when the remote token targets another audience', function () {
    config()->set('chief.auth.audiences.mcp', 'https://resource.service.invalid/mcp');

    $response = app(EnsureTokenHasAudience::class)->handle(
        audienceMiddlewareRequest('https://other.service.invalid/mcp'),
        static fn (): Response => new Response('allowed'),
        'mcp',
    );

    expect($response->getStatusCode())->toBe(401);
});

it('returns 401 when no remote token is attached', function () {
    config()->set('chief.auth.audiences.mcp', 'https://resource.service.invalid/mcp');

    $response = app(EnsureTokenHasAudience::class)->handle(
        Request::create('/mcp'),
        static fn (): Response => new Response('allowed'),
        'mcp',
    );

    expect($response->getStatusCode())->toBe(401);
});

it('fails when the named audience is not configured with an absolute URI', function (?string $audience) {
    config()->set('chief.auth.audiences.invalid', $audience);

    app(EnsureTokenHasAudience::class)->handle(
        audienceMiddlewareRequest('https://resource.service.invalid/mcp'),
        static fn (): Response => new Response('allowed'),
        'invalid',
    );
})->throws(LogicException::class, 'Chief token audience [invalid] is not configured with a valid absolute URI.')
  ->with([
      'missing'  => null,
      'relative' => '/mcp',
      'fragment' => 'https://resource.service.invalid/mcp#fragment',
  ]);

function audienceMiddlewareRequest(?string $audience = null): Request
{
    $token = new ChiefRemoteAccessToken(
        id: 'synthetic-client',
        name: 'Synthetic integration',
        prefix: 'cto',
        scopes: ['synthetic:read'],
        userId: '00000000-0000-4000-8000-000000000789',
        teamId: null,
        expiresAt: null,
        audience: $audience,
    );

    $user = (new User)->withChiefRemoteAccessToken($token);

    $request = Request::create('/mcp');
    $request->setUserResolver(static fn (): User => $user);

    return $request;
}
