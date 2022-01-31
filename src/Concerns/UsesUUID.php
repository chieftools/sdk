<?php

namespace IronGate\Chief\Concerns;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Builder;

trait UsesUUID
{
    public static function bootUsesUUID(): void
    {
        self::creating(static function (self $entity) {
            $entity->ensureUUIDExists();
        });
    }

    public function initializeUsesUUID(): void
    {
        // If the UUID is the primary key we set some attributes to have Eloquent play nice with it
        if ($this->getKeyName() === $this->getUUIDAttributeName()) {
            $this->keyType      = 'string';
            $this->incrementing = false;
        }
    }


    public function ensureUUIDExists(): void
    {
        if (empty($this->getAttribute($this->getUUIDAttributeName()))) {
            $this->setAttribute($this->getUUIDAttributeName(), Uuid::uuid4()->toString());
        }
    }


    public function scopeUuid(Builder $query, string $uuid): void
    {
        $query->where(function (Builder $query) use ($uuid) {
            $query->where($this->getUUIDAttributeName(), '=', $uuid);
        });
    }

    public function getUUIDAttributeName(): string
    {
        return $this->getKeyName();
    }


    public static function findByUuid(string $uuid): ?self
    {
        return self::query()->uuid($uuid)->first();
    }

    public static function findOrFailByUuid(string $uuid): self
    {
        return self::query()->uuid($uuid)->firstOrFail();
    }
}
