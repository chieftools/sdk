<?php

namespace ChiefTools\SDK\API\DTO;

use Carbon\Carbon;
use RuntimeException;
use Illuminate\Contracts\Support\Arrayable;

class InvoiceLine implements Arrayable
{
    public function __construct(
        public readonly string $id,
        public readonly string $description,
        public readonly int $amount,
        public readonly ?Carbon $periodStart = null,
        public readonly ?Carbon $periodEnd = null,
        public readonly ?string $categoryKey = null,
        public readonly ?string $categoryLabel = null,
    ) {
        if (($this->periodStart === null) !== ($this->periodEnd === null)) {
            throw new RuntimeException('Both periodStart and periodEnd must be provided together.');
        }

        if (($this->categoryKey === null) !== ($this->categoryLabel === null)) {
            throw new RuntimeException('Both categoryKey and categoryLabel must be provided together.');
        }
    }

    public function toArray(): array
    {
        $data = [
            'id'          => $this->id,
            'description' => $this->description,
            'amount'      => $this->amount,
        ];

        if ($this->periodStart !== null && $this->periodEnd !== null) {
            $data['period'] = [
                'start' => $this->periodStart->toDateString(),
                'end'   => $this->periodEnd->toDateString(),
            ];
        }

        if ($this->categoryKey !== null && $this->categoryLabel !== null) {
            $data['category'] = [
                'key'   => $this->categoryKey,
                'label' => $this->categoryLabel,
            ];
        }

        return $data;
    }
}
