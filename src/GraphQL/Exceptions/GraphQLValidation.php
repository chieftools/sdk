<?php

namespace IronGate\Chief\GraphQL\Exceptions;

use Illuminate\Validation\Validator;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Exceptions\RendersErrorsExtensions;
use Illuminate\Validation\ValidationException as IlluminateValidationException;

class GraphQLValidation extends IlluminateValidationException implements RendersErrorsExtensions
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

    public function getCategory(): string
    {
        return 'validation';
    }

    public function extensionsContent(): array
    {
        return ['validation' => $this->errors()];
    }
}
