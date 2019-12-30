<?php

namespace IronGate\Integration\GraphQL\Scalars;

class IP extends ValidatedStringScalar
{
    protected static $validationRule = ['ipv4', 'ipv6'];
}
