<?php

namespace App\Modules\Compliance\Console\Commands;

use App\Modules\Compliance\Jobs\CheckSLABreachesJob;
use Illuminate\Console\Command;

class CheckSLABreaches extends Command
{
    protected $signature = 'compliance:check-sla';
    protected $description = 'Detect and mark SLA breaches';

    public function handle(): int
    {
        CheckSLABreachesJob::dispatch();
        $this->info('SLA breach check dispatched.');
        return Command::SUCCESS;
    }
}
