<?php

namespace Tests\Fixtures\Webhooks;

use Dedoc\Scramble\Attributes\SchemaName;
use Illuminate\Http\Resources\Json\JsonResource;

#[SchemaName('Lantern')]
class LanternResource extends JsonResource
{
    /** @return array{id: string, brightness: int} */
    public function toArray($request): array
    {
        return [
            'id'         => (string)$this->resource['id'],
            'brightness' => (int)$this->resource['brightness'],
        ];
    }
}
