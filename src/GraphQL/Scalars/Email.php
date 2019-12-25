<?php

namespace IronGate\Integration\GraphQL\Scalars;

class Email extends ValidatedStringScalar
{
    protected static $validationRule = 'email';
}
