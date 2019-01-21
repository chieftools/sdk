<?php

namespace IronGate\Integration\Webhook\Handlers;

class AccountUpdated
{
    public function __invoke(array $webhookData)
    {
        $user = config('chief.auth.model')::query()->where('chief_id', '=', array_get($webhookData, 'user.id'))->first();

        if ($user !== null) {
            $user->fill([
                'name'     => array_get($webhookData, 'data.name'),
                'email'    => array_get($webhookData, 'data.email'),
                'timezone' => array_get($webhookData, 'data.timezone'),
            ])->save();
        }
    }
}
