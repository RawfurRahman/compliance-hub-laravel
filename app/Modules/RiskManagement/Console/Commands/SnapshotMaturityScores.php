<?php

namespace App\Modules\RiskManagement\Console\Commands;

use App\Modules\RiskManagement\Services\MaturityScoreService;
use App\Modules\RiskManagement\Services\RiskSnapshotService;
use Illuminate\Console\Command;

class SnapshotMaturityScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maturity:snapshot
                            {--project-id= : Take a full RiskSnapshot for the given project}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and persist today\'s GRC maturity score snapshots';

    protected MaturityScoreService $maturityScoreService;

    public function __construct(
        MaturityScoreService $maturityScoreService,
        private RiskSnapshotService $riskSnapshotService,
    ) {
        parent::__construct();
        $this->maturityScoreService = $maturityScoreService;
    }

    public function handle(): int
    {
        $this->info('Calculating maturity score snapshots...');

        $snapshots = $this->maturityScoreService->snapshotToday();

        foreach ($snapshots as $snapshot) {
            $this->line(sprintf(
                ' - %-22s %s (sample size: %d)',
                $snapshot->dimension,
                $snapshot->score_value,
                $snapshot->sample_size
            ));
        }

        $this->info('Maturity score snapshots saved for ' . now()->toDateString() . '.');

        if ($projectId = $this->option('project-id')) {
            $this->riskSnapshotService->takeSnapshot((int) $projectId, 'full');
            $this->info("Full risk snapshot taken for project {$projectId}.");
        }

        return self::SUCCESS;
    }
}
