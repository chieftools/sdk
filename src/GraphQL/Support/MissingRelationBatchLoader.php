<?php

namespace IronGate\Chief\GraphQL\Support;

use Illuminate\Database\Eloquent\Model;
use Nuwave\Lighthouse\Execution\DataLoader\RelationBatchLoader;

class MissingRelationBatchLoader extends RelationBatchLoader
{
    public function resolve(): array
    {
        if ($this->paginationArgs !== null) {
            throw new RuntimeException('The missing relation batch loader does not work in combination with pagination.');
        }

        $relation = [$this->relationName => $this->decorateBuilder];

        $models = $this->getParentModels()->loadMissing($relation);

        return $models
            ->mapWithKeys(
                function (Model $model): array {
                    return [$this->buildKey($model->getKey()) => $this->extractRelation($model)];
                }
            )
            ->all();
    }
}
