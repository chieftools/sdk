<?php

namespace ChiefTools\SDK\Console\Commands;

use ChiefTools\SDK\API\Client;
use Illuminate\Console\Command;
use ChiefTools\SDK\Entities\User;
use Illuminate\Database\Eloquent\Builder;

class SyncUsers extends Command
{
    protected $signature   = <<<COMMAND
                             chief:account:sync-users { users? : The user ID's to sync, seperated by a comma }
                                 { --all : Sync all users }
                             COMMAND;
    protected $description = 'Sync user data with the mothership.';

    public function handle(Client $mothership): int
    {
        $userIds = array_filter(array_map('trim', explode(',', $this->argument('users'))));

        if (count($userIds) === 0 && !$this->option('all')) {
            $this->error('Pass in user ID\'s or use the `--all` option to sync all users.');

            return self::FAILURE;
        }

        User::query()
            ->when(count($userIds) > 0, function (Builder $query) use ($userIds) {
                $query->whereIn('id', $userIds);
            })
            ->whereNotNull('chief_id')
            ->each(function (User $user) use ($mothership) {
                $this->info("=> Syncing user:{$user->chief_id} '{$user}' ({$user->email})");

                $userInfo = $mothership->user($user->chief_id);

                if ($userInfo === null) {
                    $this->warn('!> No info from the mothership for this user!');

                    return;
                }

                $user->updateFromRemote($userInfo);
            });

        return self::SUCCESS;
    }
}
