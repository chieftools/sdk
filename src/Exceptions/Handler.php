<?php

namespace ChiefTools\SDK\Exceptions;

use Throwable;
use Sentry\State\HubInterface;
use Illuminate\Support\Facades\View;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    public function report(Throwable $e): void
    {
        if ($this->shouldReport($e) && app()->bound(HubInterface::class)) {
            app(HubInterface::class)->captureException($e);
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
}
