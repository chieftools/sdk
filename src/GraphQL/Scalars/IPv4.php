<?php

namespace IronGate\Integration\GraphQL\Scalars;

class IPv4 extends ValidatedStringScalar
{
    protected static $validationRule = 'ipv4';
}
