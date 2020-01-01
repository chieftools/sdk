<?php

namespace IronGate\Chief\GraphQL\Scalars;

class IP extends ValidatedStringScalar
{
    protected static $validationRule = ['ipv4', 'ipv6'];
}
