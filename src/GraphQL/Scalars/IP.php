<?php

namespace ChiefTools\SDK\GraphQL\Scalars;

class IP extends ValidatedStringScalar
{
    protected static array $validationRules = ['ipv4', 'ipv6'];
}
