<?php

use Tests\TestCase;
use ChiefTools\SDK\UI\CommandPalette\Item;
use ChiefTools\SDK\UI\CommandPalette\Query;
use ChiefTools\SDK\UI\CommandPalette\Manager;
use ChiefTools\SDK\UI\CommandPalette\Provider;

uses(TestCase::class);

test('command palette query separates scope and term', function () {
    $query = new Query('Domain Chief > de', 8);

    expect($query->scoped)->toBeTrue()
        ->and($query->scope)->toBe('Domain Chief')
        ->and($query->scopeParts)->toBe(['Domain Chief'])
        ->and($query->term)->toBe('de')
        ->and($query->hasSearchableTerm())->toBeTrue();
});

test('command palette query treats the last path segment as the term', function () {
    $query = new Query('Domain Chief > Domains > example.com', 8);

    expect($query->scoped)->toBeTrue()
        ->and($query->scope)->toBe('Domain Chief > Domains')
        ->and($query->scopeParts)->toBe(['Domain Chief', 'Domains'])
        ->and($query->term)->toBe('example.com')
        ->and($query->normalizedScopeParts())->toBe(['domain chief', 'domains']);
});

test('command palette manager searches matching scoped providers', function () {
    $domainProvider = new class implements Provider
    {
        public function key(): string
        {
            return 'domainchief.domains';
        }

        public function label(): string
        {
            return 'Domains';
        }

        public function scopes(): array
        {
            return ['Domain Chief', 'Domain Chief > Domains', 'Domains'];
        }

        public function search(Query $query): iterable
        {
            return [
                new Item(
                    id: 'domain:1',
                    type: 'domain',
                    title: 'deploychief.com',
                    url: '/domains/deploychief.com',
                    category: 'Domain Chief',
                    subtitle: 'Domain Chief > Domains',
                    icon: 'fad fa-globe',
                    iconUrl: 'https://favicon.chief.tools/resolve/deploychief.com?size=48',
                    score: 940,
                ),
            ];
        }
    };

    $contactProvider = new class implements Provider
    {
        public function key(): string
        {
            return 'domainchief.contacts';
        }

        public function label(): string
        {
            return 'Contacts';
        }

        public function scopes(): array
        {
            return ['Domain Chief > Contacts', 'Contacts'];
        }

        public function search(Query $query): iterable
        {
            return [
                new Item(
                    id: 'contact:1',
                    type: 'contact',
                    title: 'Alex',
                    url: '/contacts/handle',
                    category: 'Domain Chief',
                    subtitle: 'Domain Chief > Contacts',
                    icon: 'fad fa-address-card',
                    score: 700,
                ),
            ];
        }
    };

    config([
        'chief.shell.command_palette_providers' => [
            $domainProvider,
            $contactProvider,
        ],
    ]);

    $results = app(Manager::class)->search(new Query('Domains > de', 8));

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe('domain:1')
        ->and($results->first()->toArray()['icon_url'])->toBe('https://favicon.chief.tools/resolve/deploychief.com?size=48')
        ->and($results->first()->toArray()['order'])->toBe(9060);
});

test('command palette manager prefers resource scopes over app scopes', function () {
    $domainProvider = new class implements Provider
    {
        public function key(): string
        {
            return 'domainchief.domains';
        }

        public function label(): string
        {
            return 'Domains';
        }

        public function scopes(): array
        {
            return ['Domain Chief', 'Domain Chief > Domains', 'Domains'];
        }

        public function search(Query $query): iterable
        {
            return [
                new Item('domain:1', 'domain', 'example.com', '/domains/example.com', 'Domain Chief'),
            ];
        }
    };

    $contactProvider = new class implements Provider
    {
        public function key(): string
        {
            return 'domainchief.contacts';
        }

        public function label(): string
        {
            return 'Contacts';
        }

        public function scopes(): array
        {
            return ['Domain Chief', 'Domain Chief > Contacts', 'Contacts'];
        }

        public function search(Query $query): iterable
        {
            return [
                new Item('contact:1', 'contact', 'Example contact', '/contacts/example', 'Domain Chief'),
            ];
        }
    };

    config([
        'chief.shell.command_palette_providers' => [
            $domainProvider,
            $contactProvider,
        ],
    ]);

    $results = app(Manager::class)->search(new Query('dom > exam', 8));

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe('domain:1');
});

test('command palette manager matches nested scope paths', function () {
    $domainProvider = new class implements Provider
    {
        public function key(): string
        {
            return 'domainchief.domains';
        }

        public function label(): string
        {
            return 'Domains';
        }

        public function scopes(): array
        {
            return ['Domain Chief', 'Domain Chief > Domains', 'Domains'];
        }

        public function search(Query $query): iterable
        {
            return [
                new Item('domain:1', 'domain', 'example.com', '/domains/example.com', 'Domain Chief'),
            ];
        }
    };

    $contactProvider = new class implements Provider
    {
        public function key(): string
        {
            return 'domainchief.contacts';
        }

        public function label(): string
        {
            return 'Contacts';
        }

        public function scopes(): array
        {
            return ['Domain Chief', 'Domain Chief > Contacts', 'Contacts'];
        }

        public function search(Query $query): iterable
        {
            return [
                new Item('contact:1', 'contact', 'Example contact', '/contacts/example', 'Domain Chief'),
            ];
        }
    };

    config([
        'chief.shell.command_palette_providers' => [
            $domainProvider,
            $contactProvider,
        ],
    ]);

    $results = app(Manager::class)->search(new Query('Domain Chief > dom > exam', 8));

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe('domain:1');
});

test('command palette manager falls back to app scopes', function () {
    $domainProvider = new class implements Provider
    {
        public function key(): string
        {
            return 'domainchief.domains';
        }

        public function label(): string
        {
            return 'Domains';
        }

        public function scopes(): array
        {
            return ['Domain Chief', 'Domain Chief > Domains', 'Domains'];
        }

        public function search(Query $query): iterable
        {
            return [
                new Item('domain:1', 'domain', 'example.com', '/domains/example.com', 'Domain Chief'),
            ];
        }
    };

    $contactProvider = new class implements Provider
    {
        public function key(): string
        {
            return 'domainchief.contacts';
        }

        public function label(): string
        {
            return 'Contacts';
        }

        public function scopes(): array
        {
            return ['Domain Chief', 'Domain Chief > Contacts', 'Contacts'];
        }

        public function search(Query $query): iterable
        {
            return [
                new Item('contact:1', 'contact', 'Example contact', '/contacts/example', 'Domain Chief'),
            ];
        }
    };

    config([
        'chief.shell.command_palette_providers' => [
            $domainProvider,
            $contactProvider,
        ],
    ]);

    $results = app(Manager::class)->search(new Query('Domain Chief > ex', 8));

    expect($results)->toHaveCount(2)
        ->and($results->pluck('id')->all())->toBe(['contact:1', 'domain:1']);
});

test('command palette manager ignores short unscoped terms', function () {
    config([
        'chief.shell.command_palette_providers' => [
            new class implements Provider
            {
                public function key(): string
                {
                    return 'domainchief.domains';
                }

                public function label(): string
                {
                    return 'Domains';
                }

                public function scopes(): array
                {
                    return ['Domains'];
                }

                public function search(Query $query): iterable
                {
                    return [
                        new Item('domain:1', 'domain', 'deploychief.com', '/', 'Domain Chief'),
                    ];
                }
            },
        ],
    ]);

    expect(app(Manager::class)->search(new Query('d', 8)))->toHaveCount(0);
});
