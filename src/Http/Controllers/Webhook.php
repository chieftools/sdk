<?php

namespace ChiefTools\SDK\Http\Controllers;

use RuntimeException;
use ChiefTools\SDK\Chief;
use Illuminate\Http\Request;
use ChiefTools\SDK\Webhook\WebhookEvent;
use ChiefTools\SDK\Webhook\Handlers\Handler;

class Webhook
{
    public function __invoke(Request $request): array
    {
        $event = WebhookEvent::tryFrom($request->json('event'));

        abort_if($event === null, 400, 'Invalid event type.');

        $handled = false;

        $handler = Chief::getWebhookHandler($event);

        if ($handler !== null && class_exists($handler)) {
            $handled = true;

            $handler = app($handler);

            if (!$handler instanceof Handler) {
                throw new RuntimeException('Webhook handlers need to implement the Handler interface.');
            }

            $return = $handler($request->json()->all());

            if ($return !== null) {
                return $return;
            }
        }

        return [
            'status'  => 'OK!',
            'handled' => $handled,
        ];
    }
}
