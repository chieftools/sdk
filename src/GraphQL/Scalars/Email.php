<?php

namespace ChiefTools\SDK\GraphQL\Scalars;

class Email extends ValidatedStringScalar
{
    protected static array $validationRules = ['email'];
}
