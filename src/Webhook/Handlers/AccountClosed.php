<?php

namespace IronGate\Integration\Webhook\Handlers;

class AccountClosed
{
    public function __invoke(array $webhookData)
    {
        /** @var \IronGate\Integration\Entities\User|null $user */
        $user = config('chief.auth.model')::query()->where('chief_id', '=', array_get($webhookData, 'data.id'))->first();

        if ($user !== null) {
            $user->delete();
        }
    }
}
