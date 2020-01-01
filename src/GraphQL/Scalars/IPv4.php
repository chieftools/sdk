<?php

namespace IronGate\Chief\GraphQL\Scalars;

class IPv4 extends ValidatedStringScalar
{
    protected static $validationRule = 'ipv4';
}
