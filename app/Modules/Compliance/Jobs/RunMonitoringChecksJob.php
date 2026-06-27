<?php

namespace App\Modules\Compliance\Jobs;

use App\Modules\Compliance\Services\ControlMonitorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunMonitoringChecksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?int $monitorId = null;

    public function __construct(?int $monitorId = null)
    {
        $this->monitorId = $monitorId;
    }

    public function handle(ControlMonitorService $service): void
    {
        if ($this->monitorId) {
            $monitor = \App\Modules\Compliance\Models\ControlMonitor::find($this->monitorId);
            if ($monitor) {
                $service->runCheck($monitor);
            }
        } else {
            $service->runAllDue();
        }
    }

    public function shouldQueue(): bool
    {
        return true;
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(60);
    }

    public function backoff(): int|array
    {
        return 30;
    }
}
