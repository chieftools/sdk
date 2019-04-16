<?php

namespace IronGate\Integration\Console\Commands;

use Illuminate\Console\Command;
use IronGate\Integration\API\Client;

class RefreshApps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chief:refresh-apps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh available Chief Apps';

    /**
     * Execute the console command.
     *
     * @param \IronGate\Integration\API\Client $api
     */
    public function handle(Client $api): void
    {
        // Retrieve all apps (except this one) that require authentication
        $apps = $api->apps(config('chief.id'), null, true);

        if ($apps->isEmpty()) {
            throw new \RuntimeException('There are no apps to store!');
        }

        $output = "<?php\n\nreturn " . var_export($apps->all(), true) . ';' . PHP_EOL;

        file_put_contents(config_path('chief_apps.php'), $output);

        $this->info('Stored ' . $apps->count() . ' apps in the config folder!');
    }
}
