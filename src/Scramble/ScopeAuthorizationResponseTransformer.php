<?php

namespace ChiefTools\SDK\Scramble;

use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Contracts\OperationTransformer;
use Illuminate\Auth\Access\AuthorizationException;
use Dedoc\Scramble\Support\Generator\TypeTransformer;

/**
 * Documents the 403 response returned by the `scope` middleware when the token lacks the required scope.
 *
 * Scramble only infers a 403 from `can` middleware or authorization in the action, it does not analyze the `scope` middleware.
 * Operation transformers run after Scramble collected the responses, so the response is added to the operation directly.
 */
final readonly class ScopeAuthorizationResponseTransformer implements OperationTransformer
{
    public function __construct(
        private TypeTransformer $openApiTransformer,
    ) {}

    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $hasScopeMiddleware = collect($routeInfo->route->gatherMiddleware())
            ->contains(static fn (mixed $middleware): bool => is_string($middleware) && str_starts_with($middleware, 'scope:'));

        if (!$hasScopeMiddleware || $this->documentsForbiddenResponse($operation)) {
            return;
        }

        $response = $this->openApiTransformer->toResponse(new ObjectType(AuthorizationException::class));

        if ($response !== null) {
            $operation->addResponse($response);
        }
    }

    private function documentsForbiddenResponse(Operation $operation): bool
    {
        return collect($operation->responses ?? [])->contains(static function (mixed $response): bool {
            if ($response instanceof Reference && $response->uri === null) {
                $response = $response->resolve();
            }

            return $response instanceof Response && $response->code === 403;
        });
    }
}
