<?php

namespace IronGate\Chief\Webhook\Handlers;

class AccountClosed extends BaseHandler
{
    public function __invoke(array $payload): ?array
    {
        $this->getUserFromPayload($payload)?->delete();

        return null;
    }
}
