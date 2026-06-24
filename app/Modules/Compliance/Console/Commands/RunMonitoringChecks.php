<?php

namespace App\Modules\Compliance\Console\Commands;

use App\Modules\Compliance\Jobs\RunMonitoringChecksJob;
use Illuminate\Console\Command;

class RunMonitoringChecks extends Command
{
    protected $signature = 'compliance:run-monitoring {--monitor-id=}';
    protected $description = 'Execute all due monitoring checks (or a specific one)';

    public function handle(): int
    {
        $monitorId = $this->option('monitor-id');

        RunMonitoringChecksJob::dispatch($monitorId ? (int) $monitorId : null);

        $this->info($monitorId ? "Monitoring check #{$monitorId} dispatched." : 'All due monitoring checks dispatched.');
        return Command::SUCCESS;
    }
}
