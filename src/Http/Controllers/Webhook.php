<?php

namespace IronGate\Chief\Http\Controllers;

use RuntimeException;
use Illuminate\Http\Request;
use IronGate\Chief\Webhook\Handlers\Handler;

class Webhook
{
    public function __invoke(Request $request): array
    {
        $event = $request->json('event');

        $handler = config("chief.webhooks.{$event}");

        if ($handler !== null && class_exists($handler)) {
            $handler = app($handler);

            if (!$handler instanceof Handler) {
                throw new RuntimeException('Webhook handlers need to implement the Handler interface.');
            }

            $return = $handler($request->json()?->all() ?? []);

            if ($return !== null) {
                return $return;
            }
        }

        return ['status' => 'OK!'];
    }
}
