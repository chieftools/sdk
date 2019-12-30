<?php

namespace IronGate\Integration\GraphQL\Scalars;

class IPv6 extends ValidatedStringScalar
{
    protected static $validationRule = 'ipv6';
}
