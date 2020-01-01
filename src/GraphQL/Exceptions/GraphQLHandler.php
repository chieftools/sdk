<?php

namespace IronGate\Chief\GraphQL\Exceptions;

use Closure;
use Sentry\State\Scope;
use GraphQL\Error\Debug;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use Nuwave\Lighthouse\Execution\ErrorHandler;

class GraphQLHandler implements ErrorHandler
{
    /**
     * This function receives all GraphQL errors and may alter them or do something else with them.
     *
     * Always call $next($error) to keep the Pipeline going. Multiple such Handlers may be registered
     * as an array in the config.
     *
     * @param \GraphQL\Error\Error $error
     * @param \Closure             $next
     *
     * @return array
     */
    public static function handle(Error $error, Closure $next): array
    {
        if (app()->bound('sentry')) {
            $eventId = null;

            app('sentry')->withScope(function (Scope $scope) use ($error, &$eventId) {
                $scope->setExtra('details', FormattedError::createFromException($error, Debug::INCLUDE_DEBUG_MESSAGE));
                $scope->setExtra('clientSafe', $error->isClientSafe());

                $eventId = app('sentry')->captureException($error);
            });

            if (!empty($eventId)) {
                $error = new Error(
                    $error->getMessage(),
                    $error->getNodes(),
                    $error->getSource(),
                    $error->getPositions(),
                    $error->getPath(),
                    $error->getPrevious(),
                    array_merge($error->getExtensions(), compact('eventId'))
                );
            }
        }

        return $next($error);
    }
}
