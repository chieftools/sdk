<?php

namespace IronGate\Chief\Http\Controllers;

use Illuminate\Http\Request;

class Webhook
{
    public function __invoke(Request $request): array
    {
        $event = $request->json('event');

        $handler = config("chief.webhooks.{$event}");

        if (class_exists($handler)) {
            $return = (new $handler)($request->json()->all());

            if (is_array($return)) {
                return $return;
            }
        }

        return ['status' => 'OK!'];
    }
}
