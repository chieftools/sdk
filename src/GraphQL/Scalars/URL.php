<?php

namespace IronGate\Integration\GraphQL\Scalars;

class URL extends ValidatedStringScalar
{
    protected static $validationRule = 'url';
}
