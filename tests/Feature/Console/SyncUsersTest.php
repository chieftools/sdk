<?php

use ChiefTools\SDK\Chief;
use ChiefTools\SDK\API\Client;
use ChiefTools\SDK\Entities\User;
use ChiefTools\SDK\Socialite\ChiefUser;
use Tests\Fixtures\Models\User as CustomUser;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('syncs users through the configured user model', function () {
    $chiefId = 'a3dd31b1-8ff1-439a-8027-6ad725527271';
    $user    = new CustomUser;
    $user->forceFill([
        'chief_id' => $chiefId,
        'name'     => 'Casey Pine',
        'email'    => 'casey@pine.example',
        'password' => 'synthetic-password',
        'timezone' => 'Europe/Amsterdam',
    ])->save();

    $remote = new ChiefUser([
        'id'              => $chiefId,
        'name'            => 'Casey Pine Updated',
        'email'           => 'casey@pine.example',
        'timezone'        => 'Europe/London',
        'avatar_hash'     => 'pine73',
        'default_team_id' => null,
        'teams'           => [],
    ]);

    $client = $this->mock(Client::class);
    $client->shouldReceive('user')->once()->with($chiefId)->andReturn($remote);

    CustomUser::$updatedChiefIds = [];
    Chief::useUserModel(CustomUser::class);

    try {
        $this->artisan('chief:account:sync-users', ['--all' => true])
            ->assertSuccessful();
    } finally {
        Chief::useUserModel(User::class);
    }

    expect(CustomUser::$updatedChiefIds)->toBe([$chiefId])
        ->and($user->fresh()->name)->toBe('Casey Pine Updated');
});
