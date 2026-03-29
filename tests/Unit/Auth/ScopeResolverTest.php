<?php

use ChiefTools\SDK\Auth\ScopeResolver;

test('exact match', function () {
    expect(ScopeResolver::satisfies(['domainchief:dns:read'], 'domainchief:dns:read'))->toBeTrue();
    expect(ScopeResolver::satisfies(['domainchief:dns:read'], 'domainchief:dns:write'))->toBeFalse();
});

test('app id grants all children', function () {
    expect(ScopeResolver::satisfies(['domainchief'], 'domainchief:dns:read'))->toBeTrue();
    expect(ScopeResolver::satisfies(['domainchief'], 'domainchief:domains:write'))->toBeTrue();
    expect(ScopeResolver::satisfies(['domainchief'], 'domainchief:domains:read:availability'))->toBeTrue();
});

test('parent grants children via prefix walk', function () {
    expect(ScopeResolver::satisfies(['domainchief:domains'], 'domainchief:domains:read'))->toBeTrue();
    expect(ScopeResolver::satisfies(['domainchief:domains'], 'domainchief:domains:write'))->toBeTrue();
    expect(ScopeResolver::satisfies(['domainchief:domains'], 'domainchief:domains:register'))->toBeTrue();
    expect(ScopeResolver::satisfies(['domainchief:domains:read'], 'domainchief:domains:read:availability'))->toBeTrue();
});

test('parent does not grant siblings', function () {
    expect(ScopeResolver::satisfies(['domainchief:dns:read'], 'domainchief:dns:write'))->toBeFalse();
    expect(ScopeResolver::satisfies(['domainchief:domains:read'], 'domainchief:domains:write'))->toBeFalse();
    expect(ScopeResolver::satisfies(['domainchief:dns'], 'domainchief:domains:read'))->toBeFalse();
});

test('write implies read at same level', function () {
    expect(ScopeResolver::satisfies(['domainchief:dns:write'], 'domainchief:dns:read'))->toBeTrue();
    expect(ScopeResolver::satisfies(['domainchief:contacts:write'], 'domainchief:contacts:read'))->toBeTrue();
    expect(ScopeResolver::satisfies(['domainchief:domains:write'], 'domainchief:domains:read'))->toBeTrue();
});

test('write does not imply read at different level', function () {
    expect(ScopeResolver::satisfies(['domainchief:dns:write'], 'domainchief:contacts:read'))->toBeFalse();
});

test('cross cutting read covers resource reads', function () {
    expect(ScopeResolver::satisfies(['domainchief:read'], 'domainchief:dns:read'))->toBeTrue();
    expect(ScopeResolver::satisfies(['domainchief:read'], 'domainchief:contacts:read'))->toBeTrue();
    expect(ScopeResolver::satisfies(['domainchief:read'], 'domainchief:domains:read'))->toBeTrue();
    expect(ScopeResolver::satisfies(['domainchief:read'], 'domainchief:activity:read'))->toBeTrue();
});

test('cross cutting read does not cover writes', function () {
    expect(ScopeResolver::satisfies(['domainchief:read'], 'domainchief:dns:write'))->toBeFalse();
    expect(ScopeResolver::satisfies(['domainchief:read'], 'domainchief:contacts:write'))->toBeFalse();
});

test('cross cutting read does not cover special actions', function () {
    expect(ScopeResolver::satisfies(['domainchief:read'], 'domainchief:domains:register'))->toBeFalse();
    expect(ScopeResolver::satisfies(['domainchief:read'], 'domainchief:domains:transfer'))->toBeFalse();
});

test('cross cutting write covers resource writes', function () {
    expect(ScopeResolver::satisfies(['domainchief:write'], 'domainchief:dns:write'))->toBeTrue();
    expect(ScopeResolver::satisfies(['domainchief:write'], 'domainchief:contacts:write'))->toBeTrue();
});

test('cross cutting write covers resource reads', function () {
    expect(ScopeResolver::satisfies(['domainchief:write'], 'domainchief:dns:read'))->toBeTrue();
    expect(ScopeResolver::satisfies(['domainchief:write'], 'domainchief:contacts:read'))->toBeTrue();
    expect(ScopeResolver::satisfies(['domainchief:write'], 'domainchief:domains:read'))->toBeTrue();
});

test('cross cutting write does not cover special actions', function () {
    expect(ScopeResolver::satisfies(['domainchief:write'], 'domainchief:domains:register'))->toBeFalse();
    expect(ScopeResolver::satisfies(['domainchief:write'], 'domainchief:domains:transfer'))->toBeFalse();
});

test('multiple granted scopes', function () {
    $granted = ['domainchief:dns:read', 'domainchief:contacts:write'];

    expect(ScopeResolver::satisfies($granted, 'domainchief:dns:read'))->toBeTrue();
    expect(ScopeResolver::satisfies($granted, 'domainchief:contacts:write'))->toBeTrue();
    expect(ScopeResolver::satisfies($granted, 'domainchief:contacts:read'))->toBeTrue(); // write implies read
    expect(ScopeResolver::satisfies($granted, 'domainchief:dns:write'))->toBeFalse();
    expect(ScopeResolver::satisfies($granted, 'domainchief:domains:read'))->toBeFalse();
});

test('wildcard scope is not handled by resolver', function () {
    expect(ScopeResolver::satisfies(['*'], 'domainchief:dns:read'))->toBeFalse();
});

test('empty granted scopes', function () {
    expect(ScopeResolver::satisfies([], 'domainchief:dns:read'))->toBeFalse();
});

test('single segment exact match', function () {
    expect(ScopeResolver::satisfies(['domainchief'], 'domainchief'))->toBeTrue();
    expect(ScopeResolver::satisfies(['certchief'], 'domainchief'))->toBeFalse();
});

test('prefix must align on segment boundary', function () {
    // "domain" is a string prefix of "domainchief" but not a segment prefix
    expect(ScopeResolver::satisfies(['domain'], 'domainchief:dns:read'))->toBeFalse();
    expect(ScopeResolver::satisfies(['domainchief:d'], 'domainchief:dns:read'))->toBeFalse();
});

test('unrelated scopes', function () {
    expect(ScopeResolver::satisfies(['certchief'], 'domainchief:dns:read'))->toBeFalse();
    expect(ScopeResolver::satisfies(['certchief:read'], 'domainchief:dns:read'))->toBeFalse();
});

test('deeply nested availability scope', function () {
    // domains:read grants domains:read:availability via prefix walk
    expect(ScopeResolver::satisfies(['domainchief:domains:read'], 'domainchief:domains:read:availability'))->toBeTrue();

    // Just availability doesn't grant broader read
    expect(ScopeResolver::satisfies(['domainchief:domains:read:availability'], 'domainchief:domains:read'))->toBeFalse();

    // App-level grants availability
    expect(ScopeResolver::satisfies(['domainchief'], 'domainchief:domains:read:availability'))->toBeTrue();

    // Cross-cutting read grants availability transitively:
    // domainchief:read → domainchief:domains:read (cross-cutting) → domainchief:domains:read:availability (prefix walk)
    expect(ScopeResolver::satisfies(['domainchief:read'], 'domainchief:domains:read:availability'))->toBeTrue();

    // Cross-cutting write also grants availability transitively (write implies read):
    // domainchief:write → domainchief:domains:read (cross-cutting write covers reads) → availability (prefix walk)
    expect(ScopeResolver::satisfies(['domainchief:write'], 'domainchief:domains:read:availability'))->toBeTrue();

    // domains:write grants availability transitively (write implies read at same level):
    // domainchief:domains:write → domainchief:domains:read (write implies read) → availability (prefix walk)
    expect(ScopeResolver::satisfies(['domainchief:domains:write'], 'domainchief:domains:read:availability'))->toBeTrue();
});
