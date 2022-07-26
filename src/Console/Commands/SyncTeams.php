<?php

namespace ChiefTools\SDK\Console\Commands;

use Exception;
use ChiefTools\SDK\API\Client;
use Illuminate\Console\Command;
use ChiefTools\SDK\Entities\Team;

class SyncTeams extends Command
{
    protected $signature   = <<<COMMAND
                             chief:account:sync-teams { teams? : The team ID's to sync, seperated by a comma }
                                 { --all : Sync all teams }
                                 { --beta : Activate the beta plan }
                             COMMAND;
    protected $description = 'Sync team data with the mothership.';

    public function handle(Client $mothership): int
    {
        $teamIds = array_filter(array_map('trim', explode(',', $this->argument('teams'))));

        if (count($teamIds) === 0 && !$this->option('all')) {
            $this->error('Pass in team ID\'s or use the `--all` option to sync all teams.');

            return self::FAILURE;
        }

        Team::query()
            ->each(function (Team $team) use ($mothership) {
                $this->info("=> Syncing team:{$team->id} '{$team}' ({$team->slug})");

                $teamInfo = $mothership->team($team->slug);

                if ($teamInfo === null) {
                    $this->warn('!> No info from the mothership for this team!');

                    return;
                }

                $team->updateFromRemote($teamInfo);

                if ($this->option('beta')) {
                    try {
                        $mothership->activateBetaPlan($team->slug);
                    } catch (Exception) {
                        $this->warn('!> Could not activate beta plan for this team!');
                    }
                }
            });

        return self::SUCCESS;
    }
}
