<?php

namespace ChiefTools\SDK\GraphQL\Exceptions;

use Closure;
use Sentry\State\Scope;
use GraphQL\Error\Error;
use GraphQL\Error\DebugFlag;
use Sentry\State\HubInterface;
use GraphQL\Error\FormattedError;
use Nuwave\Lighthouse\Execution\ErrorHandler;

class GraphQLHandler implements ErrorHandler
{
    public function __invoke(?Error $error, Closure $next): ?array
    {
        // Client-safe errors are assumed to be something that a client can handle
        // or is expected to happen, e.g. wrong syntax, authentication or validation
        if ($error === null || $error->isClientSafe()) {
            return $next($error);
        }

        if (app()->bound(HubInterface::class)) {
            $eventId = null;

            app(HubInterface::class)->withScope(function (Scope $scope) use ($error, &$eventId) {
                $scope->setExtra('details', FormattedError::createFromException($error, DebugFlag::INCLUDE_DEBUG_MESSAGE));
                $scope->setExtra('clientSafe', $error->isClientSafe());

                $eventId = app(HubInterface::class)->captureException($error);
            });

            if ($eventId !== null) {
                $error = new Error(
                    $error->getMessage(),
                    $error->getNodes(),
                    $error->getSource(),
                    $error->getPositions(),
                    $error->getPath(),
                    $error->getPrevious(),
                    array_merge($error->getExtensions(), compact('eventId')),
                );
            }
        }

        return $next($error);
    }
}
