<?php

namespace App\Modules\Compliance\Console\Commands;

use App\Modules\Compliance\Jobs\GenerateComplianceSnapshotJob;
use App\Models\Project;
use Illuminate\Console\Command;

class GenerateComplianceSnapshot extends Command
{
    protected $signature = 'compliance:snapshot {--project-id=} {--type=ondemand}';
    protected $description = 'Generate a compliance snapshot for a project';

    public function handle(): int
    {
        $projectId = $this->option('project-id');
        $type = $this->option('type');

        if ($projectId) {
            GenerateComplianceSnapshotJob::dispatch((int) $projectId, $type);
            $this->info("Snapshot for project #{$projectId} dispatched.");
        } else {
            $projectIds = Project::pluck('id');
            foreach ($projectIds as $id) {
                GenerateComplianceSnapshotJob::dispatch($id, $type);
            }
            $this->info('Snapshots for all projects dispatched.');
        }

        return Command::SUCCESS;
    }
}
