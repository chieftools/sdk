<?php

namespace IronGate\Chief\Webhook\Handlers;

class AccountUpdated extends BaseHandler
{
    public function __invoke(array $payload): ?array
    {
        $user = $this->getUserFromPayload($payload);

        if ($user === null) {
            return null;
        }

        $user->fill([
            'name'     => array_get($payload, 'data.name'),
            'email'    => array_get($payload, 'data.email'),
            'timezone' => array_get($payload, 'data.timezone'),
        ]);

        $user->is_admin = ((bool)array_get($payload, 'data.is_admin', false)) === true;

        $user->save();

        return null;
    }
}
