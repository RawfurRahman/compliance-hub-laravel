<?php

namespace App\Console\Commands;

use App\Services\MaturityScoreService;
use Illuminate\Console\Command;

class SnapshotMaturityScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maturity:snapshot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and persist today\'s GRC maturity score snapshots';

    protected MaturityScoreService $maturityScoreService;

    public function __construct(MaturityScoreService $maturityScoreService)
    {
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

        return self::SUCCESS;
    }
}
