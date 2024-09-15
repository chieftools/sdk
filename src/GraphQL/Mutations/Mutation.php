<?php

namespace ChiefTools\SDK\GraphQL\Mutations;

use Exception;
use InvalidArgumentException;
use ChiefTools\SDK\GraphQL\QueryResolver;
use ChiefTools\SDK\GraphQL\Exceptions\GraphQLErrorResponse;

abstract class Mutation extends QueryResolver
{
    private static array $errorMap = [
        404 => 'Not Found',
    ];

    public function rules(): array
    {
        return [];
    }

    public function before(): void
    {
        // Empty on purpose to allow child classes to not have to define it
    }

    protected function execute(): array
    {
        $validationRules = $this->rules();

        try {
            if (!empty($validationRules)) {
                $validator = validator($this->args['input'], $validationRules);

                if ($validator->fails()) {
                    return $this->errorResponse($validator->getMessageBag()->toArray());
                }
            }

            $this->before();

            return $this->successResponse(
                $this->mutate(),
            );
        } catch (Exception $exception) {
            return $this->resolveErrors($exception);
        }
    }

    abstract public function mutate(): array|bool|null;

    protected function abortUnless(bool $test, string $field = 'id', int $status = 404): void
    {
        if ($test) {
            return;
        }

        if (empty(self::$errorMap[$status])) {
            throw new InvalidArgumentException('Abort status code does not have a message.');
        }

        $this->errorResponse([
            $field => trans(self::$errorMap[$status]),
        ]);
    }

    private function successResponse(array|bool|null $response): array
    {
        $success = [
            'status' => [
                'success' => $response !== false,
            ],
        ];

        if ($response === null || $response === true) {
            return $success;
        }

        return array_merge($response, $success);
    }

    private function resolveErrors(Exception $exception): array
    {
        if (!($exception instanceof GraphQLErrorResponse) && config('app.debug')) {
            throw $exception;
        }

        $errorId = null;

        if (!($exception instanceof GraphQLErrorResponse)) {
            $errorId = capture_exception_to_sentry($exception);
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
}
