<?php

namespace ChiefTools\SDK\Webhook\Handlers;

interface Handler
{
    public function __invoke(array $payload): ?array;
}
