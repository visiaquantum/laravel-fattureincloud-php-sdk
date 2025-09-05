<?php

namespace Codeman\LaravelFattureInCloudPhpSdk\Commands;

use Illuminate\Console\Command;

class LaravelFattureInCloudPhpSdkCommand extends Command
{
    public $signature = 'laravel-fattureincloud-php-sdk';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
