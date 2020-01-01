<?php

namespace IronGate\Chief\Webhook\Handlers;

class AccountUpdated
{
    public function __invoke(array $webhookData)
    {
        /** @var \IronGate\Chief\Entities\User|null $user */
        $user = config('chief.auth.model')::query()->where('chief_id', '=', array_get($webhookData, 'user.id'))->first();

        if ($user !== null) {
            $user->fill([
                'name'     => array_get($webhookData, 'data.name'),
                'email'    => array_get($webhookData, 'data.email'),
                'timezone' => array_get($webhookData, 'data.timezone'),
            ]);

            $user->is_admin = ((bool)array_get($webhookData, 'data.is_admin', false)) === true;

            $user->save();
        }
    }
}
