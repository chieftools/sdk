<?php

namespace IronGate\Chief\GraphQL\Support;

use RuntimeException;
use Illuminate\Database\Eloquent\Model;
use Nuwave\Lighthouse\Execution\Utils\ModelKey;
use Nuwave\Lighthouse\Execution\DataLoader\RelationFetcher;
use Nuwave\Lighthouse\Execution\DataLoader\RelationBatchLoader;

class MissingRelationBatchLoader extends RelationBatchLoader
{
    /**
     * {@inheritdoc}
     */
    public function resolve(): array
    {
        $relation = [$this->relationName => $this->decorateBuilder];

        if ($this->paginationArgs !== null) {
            throw new RuntimeException('The missing relation batch loader does not work in combination with pagination.');
        }

        $models = RelationFetcher::extractParentModels($this->keys)->loadMissing($relation);

        return $models->mapWithKeys(function (Model $model): array {
            return [ModelKey::build($model) => $this->extractRelation($model)];
        })->all();
    }
}
