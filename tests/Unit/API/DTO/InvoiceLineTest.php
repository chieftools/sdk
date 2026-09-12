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

it('serializes a complete resource', function () {
    $line = new InvoiceLine(
        id: 'operation_synthetic_4',
        description: 'Synthetic managed resource renewal',
        amount: 1175,
        resourceId: 'resource_synthetic_1',
        resourceType: 'domain',
    );

    expect($line->toArray())->toBe([
        'id'          => 'operation_synthetic_4',
        'description' => 'Synthetic managed resource renewal',
        'amount'      => 1175,
        'resource'    => [
            'id'   => 'resource_synthetic_1',
            'type' => 'domain',
        ],
    ]);
});

it('serializes an optional resource label', function () {
    $line = new InvoiceLine(
        id: 'operation_synthetic_7',
        description: 'Synthetic managed zone renewal',
        amount: 1275,
        resourceId: 'resource_synthetic_4',
        resourceType: 'domain',
        resourceLabel: 'managed-zone.example',
    );

    expect($line->toArray())->toBe([
        'id'          => 'operation_synthetic_7',
        'description' => 'Synthetic managed zone renewal',
        'amount'      => 1275,
        'resource'    => [
            'id'    => 'resource_synthetic_4',
            'type'  => 'domain',
            'label' => 'managed-zone.example',
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

it('requires both resource fields', function (?string $resourceId, ?string $resourceType) {
    expect(fn () => new InvoiceLine(
        id: 'operation_synthetic_5',
        description: 'Synthetic managed resource operation',
        amount: 925,
        resourceId: $resourceId,
        resourceType: $resourceType,
    ))->toThrow(RuntimeException::class, 'Both resourceId and resourceType must be provided together.');
})->with([
    'missing id'   => [null, 'domain'],
    'missing type' => ['resource_synthetic_2', null],
]);

it('requires a resource when a resource label is provided', function () {
    expect(fn () => new InvoiceLine(
        id: 'operation_synthetic_8',
        description: 'Synthetic labeled resource operation',
        amount: 1025,
        resourceLabel: 'labeled-resource.example',
    ))->toThrow(RuntimeException::class, 'resourceLabel can only be provided with resourceId and resourceType.');
});
