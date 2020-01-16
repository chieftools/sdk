<?php

namespace IronGate\Chief\Concerns;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

trait UsesUUID
{
    public static function bootUsesUUID(): void
    {
        self::creating(function (Model $model) {
            $model->{$model->getUuidColumnName()} = Uuid::uuid4()->toString();
        });
    }

    /**
     * Find by the UUID attribute.
     *
     * @param string $uuid
     *
     * @return static|null
     */
    public static function findByUuid(string $uuid)
    {
        return (new static)->query()->uuid($uuid)->first();
    }

    /**
     * Find or fail by the UUID attribute.
     *
     * @param string $uuid
     *
     * @return static
     */
    public static function findOrFailByUuid(string $uuid)
    {
        return (new static)->query()->uuid($uuid)->firstOrFail();
    }

    public function scopeUuid(Builder $query, string $uuid): void
    {
        $query->where(function (Builder $query) use ($uuid) {
            $query->where($this->getUuidColumnName(), '=', $uuid);
        });
    }

    protected function getUuidColumnName(): string
    {
        return $this->getKeyName();
    }
}
