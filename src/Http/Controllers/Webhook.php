<?php

namespace IronGate\Integration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use IronGate\Integration\Models\User;
use IronGate\Integration\Http\Middleware\AuthenticateChief;

class Webhook extends Controller
{
    public function __construct()
    {
        $this->middleware(AuthenticateChief::class);
    }

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
