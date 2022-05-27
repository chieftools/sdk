<?php

namespace IronGate\Chief\GraphQL\Scalars;

class IPv6 extends ValidatedStringScalar
{
    protected static array $validationRules = ['ipv6'];
}
