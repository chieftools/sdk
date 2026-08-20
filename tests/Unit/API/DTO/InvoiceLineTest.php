<?php

use Carbon\Carbon;
use ChiefTools\SDK\API\DTO\InvoiceLine;

it('keeps the legacy payload unchanged without a category', function () {
    $line = new InvoiceLine(
        id: 'operation_synthetic_1',
        description: 'Synthetic service renewal',
        amount: 1450,
        periodStart: Carbon::parse('2099-03-01'),
        periodEnd: Carbon::parse('2100-03-01'),
    );

    expect($line->toArray())->toBe([
        'id'          => 'operation_synthetic_1',
        'description' => 'Synthetic service renewal',
        'amount'      => 1450,
        'period'      => [
            'start' => '2099-03-01',
            'end'   => '2100-03-01',
        ],
    ]);
});

it('serializes a complete category', function () {
    $line = new InvoiceLine(
        id: 'operation_synthetic_2',
        description: 'Synthetic service registration',
        amount: 975,
        categoryKey: 'registration',
        categoryLabel: 'Service registrations',
    );

    expect($line->toArray())->toBe([
        'id'          => 'operation_synthetic_2',
        'description' => 'Synthetic service registration',
        'amount'      => 975,
        'category'    => [
            'key'   => 'registration',
            'label' => 'Service registrations',
        ],
    ]);
});

it('requires both category fields', function (?string $categoryKey, ?string $categoryLabel) {
    expect(fn () => new InvoiceLine(
        id: 'operation_synthetic_3',
        description: 'Synthetic service operation',
        amount: 825,
        categoryKey: $categoryKey,
        categoryLabel: $categoryLabel,
    ))->toThrow(RuntimeException::class, 'Both categoryKey and categoryLabel must be provided together.');
})->with([
    'missing key'   => [null, 'Service operations'],
    'missing label' => ['operation', null],
]);
