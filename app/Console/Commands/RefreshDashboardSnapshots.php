<?php

namespace App\Console\Commands;

use App\Jobs\Dashboard\RefreshDashboardSnapshotJob;
use App\Services\Dashboard\DashboardDomains;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshDashboardSnapshots extends Command
{
    protected $signature = 'dashboard:refresh-snapshots
                            {--domain= : Single domain to refresh (kpi, heatmap, etc.)}
                            {--business-unit= : Filter by business unit}
                            {--framework= : Filter by framework}
                            {--project-id= : Filter by project}
                            {--date-scope=daily : Snapshot date scope (daily, weekly, monthly)}
                            {--sync : Run synchronously instead of dispatching jobs}';

    protected $description = 'Refresh dashboard snapshot data across all domains';

    public function handle(): int
    {
        $domains = $this->option('domain')
            ? [ $this->option('domain') ]
            : DashboardDomains::ALL;

        $businessUnit = $this->option('business-unit');
        $framework = $this->option('framework');
        $projectId = $this->option('project-id') ? (int) $this->option('project-id') : null;
        $dateScope = $this->option('date-scope');
        $sync = $this->option('sync');

        $this->info(sprintf(
            'Refreshing %d dashboard snapshot(s) (scope: %s)...',
            count($domains),
            $dateScope
        ));

        $start = microtime(true);
        $dispatched = 0;

        foreach ($domains as $domain) {
            if ($sync) {
                RefreshDashboardSnapshotJob::dispatchSync(
                    domain: $domain,
                    businessUnit: $businessUnit,
                    framework: $framework,
                    dateScope: $dateScope,
                    projectId: $projectId,
                );
                $this->line("  [OK] {$domain}");
            } else {
                RefreshDashboardSnapshotJob::dispatch(
                    domain: $domain,
                    businessUnit: $businessUnit,
                    framework: $framework,
                    dateScope: $dateScope,
                    projectId: $projectId,
                );
                $this->line("  [DISPATCHED] {$domain}");
            }
            $dispatched++;
        }

        $elapsed = round((microtime(true) - $start) * 1000, 2);

        Log::info('Dashboard refresh command completed', [
            'domains_count' => $dispatched,
            'domains' => $domains,
            'business_unit' => $businessUnit,
            'framework' => $framework,
            'date_scope' => $dateScope,
            'sync' => $sync,
            'duration_ms' => $elapsed,
        ]);

        $this->newLine();
        $this->info("Done. {$dispatched} snapshot(s) processed in {$elapsed}ms.");

        return Command::SUCCESS;
    }
}
