<?php

namespace IronGate\Integration\GraphQL\Mutations;

use Exception;
use IronGate\Integration\Entities\User;
use GraphQL\Type\Definition\ResolveInfo;
use IronGate\Integration\GraphQL\Context;
use IronGate\Integration\GraphQL\Exceptions\GraphQLErrorResponse;

abstract class Mutation
{
    /**
     * @var mixed
     */
    protected $root;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var \Nuwave\Lighthouse\Schema\Context
     */
    protected $context;

    /**
     * @var \GraphQL\Type\Definition\ResolveInfo
     */
    protected $resolveInfo;

    public function user(): User
    {
        return $this->context->user();
    }

    public function rules(): array
    {
        return [];
    }

    public function resolve($root, array $args, Context $context, ResolveInfo $info): array
    {
        $this->root        = $root;
        $this->args        = $args;
        $this->context     = $context;
        $this->resolveInfo = $info;

        $validationRules = $this->rules();

        try {
            if (!empty($validationRules)) {
                $validator = validator($this->args['input'], $validationRules);

                if ($validator->fails()) {
                    return $this->errorResponse($validator->getMessageBag()->toArray());
                }
            }

            return $this->successResponse($this->mutate());
        } catch (Exception $exception) {
            return $this->resolveErrors($exception);
        }
    }

    public function __invoke($root, array $args, Context $context, ResolveInfo $info): array
    {
        return $this->resolve($root, $args, $context, $info);
    }

    public function resolveErrors(Exception $exception): array
    {
        if (!($exception instanceof GraphQLErrorResponse) && config('app.debug')) {
            throw $exception;
        }

        $errorId = null;

        if (!($exception instanceof GraphQLErrorResponse) && app()->bound('sentry')) {
            $errorId = app('sentry')->captureException($exception);
        }

        return [
            'status' => [
                'success' => false,
                'errors'  => $exception instanceof GraphQLErrorResponse ? $exception->getErrorsForGraphQL() : [
                    [
                        'name'     => 'base',
                        'messages' => [
                            'Something went wrong executing the mutation, there is no specific error information available.',
                        ],
                    ],
                ],
                'errorId' => $errorId,
            ],
        ];
    }

    abstract public function mutate(): ?array;

    protected function input(?string $key = null, $default = null)
    {
        return array_get($this->args, $key === null ? 'input' : "input.{$key}", $default);
    }

    protected function errorResponse(array $errors): array
    {
        throw new GraphQLErrorResponse($errors);

        return [];
    }

    protected function successResponse(?array $response = null): array
    {
        $success = [
            'status' => [
                'success' => true,
            ],
        ];

        return $response === null ? $success : array_merge($response, $success);
    }
}
