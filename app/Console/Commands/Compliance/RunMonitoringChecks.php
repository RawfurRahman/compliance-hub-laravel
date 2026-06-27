<?php

namespace App\Console\Commands\Compliance;

use App\Modules\Compliance\Services\ControlMonitorService;
use Illuminate\Console\Command;

class RunMonitoringChecks extends Command
{
    protected $signature = 'compliance:run-monitoring-checks {monitorId? : ID of a specific monitor to run (optional)}';

    protected $description = 'Run one or more monitoring checks for ControlMonitors';

    public function handle(ControlMonitorService $service): int
    {
        $monitorId = $this->argument('monitorId');

        if ($monitorId) {
            $monitor = \App\Modules\Compliance\Models\ControlMonitor::find($monitorId);
            if (!$monitor) {
                $this->error("Monitor with ID {$monitorId} not found.");
                return self::FAILURE;
            }

            $this->info("Running check for monitor: {$monitor->id} - Control: {$monitor->control->control_code}");
            $finding = $service->runCheck($monitor);
            $this->line("  Result: {$finding->compliance_state}");
            $this->line("  Status: {$finding->status}");

            $this->info("Check completed.");
        } else {
            $this->info("Running all due monitoring checks...");
            $count = $service->runAllDue();
            $this->info("Ran {$count} scheduled monitoring checks.");
        }

        return self::SUCCESS;
    }
}
