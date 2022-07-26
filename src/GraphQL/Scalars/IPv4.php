<?php

namespace ChiefTools\SDK\GraphQL\Scalars;

class IPv4 extends ValidatedStringScalar
{
    protected static array $validationRules = ['ipv4'];
}
