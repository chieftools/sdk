<?php

namespace IronGate\Chief\Webhook\Handlers;

interface Handler
{
    public function __invoke(array $payload): ?array;
}
