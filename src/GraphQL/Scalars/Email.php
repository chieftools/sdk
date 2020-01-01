<?php

namespace IronGate\Chief\GraphQL\Scalars;

class Email extends ValidatedStringScalar
{
    protected static $validationRule = 'email';
}
