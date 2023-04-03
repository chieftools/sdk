<?php

namespace ChiefTools\SDK\GraphQL\Exceptions;

use GraphQL\Error\ClientAware;
use Illuminate\Validation\Validator;
use GraphQL\Error\ProvidesExtensions;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Validation\ValidationException as IlluminateValidationException;

class GraphQLValidation extends IlluminateValidationException implements ClientAware, ProvidesExtensions
{
    public function __construct(Validator $validator, ResolveInfo $info)
    {
        parent::__construct($validator);

        $this->message = 'Validation for the field [' . implode('.', $info->path) . '] failed.';
    }

    public function isClientSafe(): bool
    {
        return true;
    }

    public function getExtensions(): array
    {
        return array_merge([
            'category'   => 'validation',
            'validation' => $this->errors(),
        ]);
    }
}
