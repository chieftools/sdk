<?php

namespace ChiefTools\SDK\GraphQL\Scalars;

class URL extends ValidatedStringScalar
{
    protected static array $validationRules = ['url'];
}
