<?php

namespace Codeman\FattureInCloud\Commands;

use Illuminate\Console\Command;

class FattureInCloudCommand extends Command
{
    public $signature = 'fatture-in-cloud';

    public $description = 'FattureInCloud SDK command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
