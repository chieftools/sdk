<?php

use GraphQL\Language\Parser;
use Illuminate\Http\Request;
use Tests\Fixtures\Models\User;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\GraphQL\Context;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\Values\TypeValue;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nuwave\Lighthouse\Exceptions\AuthorizationException;
use ChiefTools\SDK\GraphQL\Directives\ViewerOnlyDirective;

uses(LazilyRefreshDatabase::class);

function viewerOnlyResolver(): Closure
{
    $field = Parser::fieldDefinition('teams: [Team!]!');

    $fieldValue = new class(new TypeValue(Parser::objectTypeDefinition('type User { id: ID! }')), $field) extends FieldValue
    {
        /** @var callable */
        public $wrapper;

        public function wrapResolver(callable $resolverWrapper): void
        {
            $this->wrapper = $resolverWrapper;
        }
    };

    (new ViewerOnlyDirective)->hydrate(Parser::directive('@viewerOnly'), $field)->handleField($fieldValue);

    return ($fieldValue->wrapper)(static fn (mixed $root): string => "resolved for {$root->name}");
}

function viewerOnlyContextFor(mixed $authenticatable): Context
{
    $request = Request::create('/api/graphql');
    $request->setUserResolver(static fn () => $authenticatable);

    return new Context($request);
}

function viewerOnlyOtherUser(): User
{
    $user = new User;
    $user->forceFill([
        'chief_id' => '8d3c1b7e-2f4a-4c6d-9e1b-5a7f3c2d1e90',
        'name'     => 'Sage Thorn',
        'email'    => 'sage@thorn.example',
        'password' => 'synthetic-password',
        'timezone' => 'Europe/Amsterdam',
    ])->save();

    return $user->refresh();
}

it('resolves the field on the authenticated user', function () {
    $viewer = createTeamContextUser();
    createTeamContextTeam('amber-grove', $viewer);

    $result = viewerOnlyResolver()($viewer, [], viewerOnlyContextFor($viewer), test()->createStub(ResolveInfo::class));

    expect($result)->toBe('resolved for Robin Vale');
});

it('refuses the field on another user', function () {
    $viewer = createTeamContextUser();
    createTeamContextTeam('amber-grove', $viewer);

    viewerOnlyResolver()(viewerOnlyOtherUser(), [], viewerOnlyContextFor($viewer), test()->createStub(ResolveInfo::class));
})->throws(AuthorizationException::class, "The 'teams' field is only available on the authenticated user.");

it('refuses the field when authenticated as a team', function () {
    $team = new Team;
    $team->forceFill(['slug' => 'fern-valley', 'name' => 'Fern Valley'])->save();

    viewerOnlyResolver()(viewerOnlyOtherUser(), [], viewerOnlyContextFor($team), test()->createStub(ResolveInfo::class));
})->throws(AuthorizationException::class);

it('refuses the field without authentication', function () {
    viewerOnlyResolver()(viewerOnlyOtherUser(), [], viewerOnlyContextFor(null), test()->createStub(ResolveInfo::class));
})->throws(AuthorizationException::class);
