<?php

namespace ChiefTools\SDK\Console\Commands;

use Exception;
use ChiefTools\SDK\API\Client;
use Illuminate\Console\Command;

class DownloadPricing extends Command
{
    protected $signature   = <<<COMMAND
                             chief:pricing:download
                             COMMAND;
    protected $description = 'Download the pricing HTML from the mothership.';

    public function handle(Client $mothership): int
    {
        try {
            file_put_contents(
                resource_path('views/site/pages/partial/_generated_pricing.blade.php'),
                $mothership->appPricing(config('chief.id'), config('chief.pricing.without_featured'))->toHtml(),
            );
        } catch (Exception $e) {
            $this->error('Woops, something wen\'t wrong trying to get the pricing!');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Downloaded and wrote the pricing HTML to the view path.');

        return self::SUCCESS;
    }
}
