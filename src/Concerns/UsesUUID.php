<?php

namespace IronGate\Integration\Concerns;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;

trait UsesUUID
{
    public static function bootUsesUUID(): void
    {
        self::creating(function (Model $model) {
            $model->{$model->getUuidColumnName()} = Uuid::uuid4()->toString();
        });
    }

    protected function getUuidColumnName(): string
    {
        return $this->getKeyName();
    }
}
