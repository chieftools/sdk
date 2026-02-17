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
    ) {
        if (($this->periodStart === null) !== ($this->periodEnd === null)) {
            throw new RuntimeException('Both periodStart and periodEnd must be provided together.');
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

        return $data;
    }
}
