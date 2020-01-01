<?php

namespace IronGate\Chief\GraphQL\Exceptions;

use Exception;

class GraphQLErrorResponse extends Exception
{
    protected array $errors;

    public function __construct(array $errors)
    {
        parent::__construct();

        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorsForGraphQL(): array
    {
        return collect($this->errors)->map(function ($messages, $name) {
            return [
                'name'     => $name,
                'messages' => (array)$messages,
            ];
        })->all();
    }
}
