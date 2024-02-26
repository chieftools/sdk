<?php

namespace ChiefTools\SDK\Exceptions;

use Throwable;
use ChiefTools\SDK\Chief;
use Sentry\Laravel\Integration;
use Illuminate\Support\Facades\View;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    public function report(Throwable $e): void
    {
        if ($this->shouldReport($e)) {
            Integration::captureUnhandledException($e);
        }

        parent::report($e);
    }

    protected function registerErrorViewPaths(): void
    {
        View::replaceNamespace(
            'errors',
            collect(config('view.paths'))
                ->map(fn ($path) => "{$path}/errors")
                ->push(__DIR__ . '/../../views/chief/errors')
                ->push(base_path('vendor/laravel/framework/src/Illuminate/Foundation/Exceptions/views'))
                ->all(),
        );
    }

    protected function invalidJson($request, ValidationException $exception)
    {
        $customHandler = Chief::getValidationExceptionJsonResponseHandler();

        if ($customHandler) {
            return $customHandler($request, $exception);
        }

        return parent::invalidJson($request, $exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($this->shouldReturnJson($request, $exception)) {
            $customHandler = Chief::getAuthenticationExceptionJsonResponseHandler();

            if ($customHandler) {
                return $customHandler($request, $exception);
            }
        }

        return parent::unauthenticated($request, $exception);
    }
}
