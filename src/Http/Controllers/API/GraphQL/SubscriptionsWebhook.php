<?php

namespace ChiefTools\SDK\Http\Controllers\API\GraphQL;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Nuwave\Lighthouse\Subscriptions\BroadcastDriverManager;

class SubscriptionsWebhook
{
    private BroadcastDriverManager $broadcastManager;

    public function __construct(BroadcastDriverManager $broadcastManager)
    {
        $this->broadcastManager = $broadcastManager;
    }

    public function __invoke(Request $request): Response
    {
        $secret = config('chief.graphql.subscriptions.webhook_secret');

        if ($secret !== null && $request->input('secret') !== $secret) {
            abort(401);
        }

        return $this->broadcastManager->hook($request);
    }
}
