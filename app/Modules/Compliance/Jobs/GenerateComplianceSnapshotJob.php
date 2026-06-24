<?php

namespace App\Modules\Compliance\Jobs;

use App\Modules\Compliance\Services\ComplianceSnapshotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateComplianceSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $projectId,
        public string $type = 'ondemand',
    ) {}

    public function handle(ComplianceSnapshotService $service): void
    {
        $service->takeSnapshot($this->projectId, $this->type);
    }
}
