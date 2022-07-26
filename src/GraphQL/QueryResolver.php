<?php

namespace ChiefTools\SDK\GraphQL;

use RuntimeException;
use Illuminate\Database;
use Illuminate\Http\Request;
use ChiefTools\SDK\Entities\User;
use Illuminate\Support\Collection;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Auth\Access\Gate;
use Nuwave\Lighthouse\Exceptions\AuthorizationException;
use ChiefTools\SDK\GraphQL\Exceptions\GraphQLErrorResponse;

/**
 * @template TRoot
 */
abstract class QueryResolver
{
    /** @var TRoot */
    protected mixed $root;

    protected array $args;

    protected Context $context;

    protected ResolveInfo $resolveInfo;

    public function __invoke($root, array $args, Context $context, ResolveInfo $resolveInfo)
    {
        $this->root        = $root;
        $this->args        = $args;
        $this->context     = $context;
        $this->resolveInfo = $resolveInfo;

        return $this->execute();
    }


    abstract protected function execute(): mixed;


    protected function input(?string $key, $default = null)
    {
        return array_get($this->args, $key, $default);
    }

    protected function filled(string $key): bool
    {
        return !$this->isEmptyString($this->input($key));
    }

    protected function nestedInput(?string $key, $default = null)
    {
        return $this->input($key === null ? 'input' : "input.{$key}", $default);
    }

    protected function nestedFilled(string $key): bool
    {
        return !$this->isEmptyString($this->nestedInput($key));
    }

    protected function directiveArg(?string $key, $default = null)
    {
        return $this->input($key === null ? 'directive' : "directive.{$key}", $default);
    }

    protected function isEmptyString($value): bool
    {
        return !is_bool($value) && !is_array($value) && trim((string)$value) === '';
    }

    protected function collectNestedInput(?string $key, $default = []): Collection
    {
        $input = $this->nestedInput($key);

        if ($input !== null && !is_array($input)) {
            throw new RuntimeException("Expected input value to be array for {$key}.");
        }

        return collect($input ?? $default);
    }


    protected function gate(): Gate
    {
        return app(Gate::class);
    }

    protected function user(): User
    {
        return $this->context->user();
    }

    protected function guest(): bool
    {
        return $this->context->user() === null;
    }

    protected function userId(): int
    {
        return $this->context->user()->id;
    }


    protected function request(): Request
    {
        return $this->context->request();
    }

    protected function authorize($ability, $args = []): void
    {
        $this->authorizeFor($this->user(), $ability, $args);
    }

    protected function authorizeFor(User $user, $ability, $args = []): void
    {
        if (!$this->gate()->forUser($user)->check($ability, $args)) {
            throw new AuthorizationException(trans('api.msg_not_authorized', ['path' => $this->fieldPath()]));
        }
    }


    protected function getPaginationPage(): int
    {
        return (int)$this->input('page', 1);
    }

    protected function getPaginationPageSize(): int
    {
        return (int)$this->input('first', config('lighthouse.pagination.default_count'));
    }

    protected function getOrderByField(string $default): string
    {
        return $this->input('orderBy.field', $default);
    }

    protected function getOrderByDirection(string $default = 'asc'): string
    {
        return $this->input('orderBy.direction', $default);
    }


    protected function fieldPath(): string
    {
        return implode('.', $this->resolveInfo->path);
    }

    protected function fieldsRequested(): array
    {
        return array_keys($this->resolveInfo->getFieldSelection());
    }

    protected function isFieldRequested(string $field): bool
    {
        $fields = $this->resolveInfo->getFieldSelection(1);

        // If we have a `data` field we asume we're paginating and check there too
        if (isset($fields['data']) && array_key_exists($field, array_filter($fields['data']))) {
            return true;
        }

        return array_key_exists($field, array_filter($fields));
    }

    protected function isDataFieldRequested(): bool
    {
        return $this->isFieldRequested('data');
    }

    protected function isPaginatorInfoFieldRequested(): bool
    {
        return $this->isFieldRequested('paginatorInfo');
    }


    protected function errorResponse(array $errors): array
    {
        throw new GraphQLErrorResponse($errors);
    }


    protected function applyOrderToQuery(Database\Query\Builder|Database\Eloquent\Builder|Database\Eloquent\Relations\Relation $query, ?string $defaultField = null, string $defaultDirection = 'asc', ?bool $qualify = false): void
    {
        $orderByField     = $defaultField;
        $orderByDirection = $defaultDirection;

        $orderInput = $this->input('orderBy');

        if (!empty($orderInput)) {
            $orderByField     = $orderInput['field'] ?? $defaultField;
            $orderByDirection = $orderInput['direction'] ?? $defaultDirection;
        }

        if ($orderByField === null) {
            return;
        }

        if ($qualify) {
            $orderByField = $query->getModel()->qualifyColumn($orderByField);
        }

        $query->reorder($orderByField, $orderByDirection)
              ->orderBy($query->getModel()->getQualifiedKeyName(), $orderByDirection);
    }
}
