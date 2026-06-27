<?php

namespace App\Console\Commands;

use App\Services\Dashboard\DashboardCacheKey;
use App\Services\Dashboard\DashboardDomains;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class InvalidateDashboardCache extends Command
{
    protected $signature = 'dashboard:invalidate-cache
                            {--domain= : Specific domain to invalidate}
                            {--business-unit= : Filter by business unit}
                            {--framework= : Filter by framework}';

    protected $description = 'Invalidate cached dashboard data';

    public function handle(): int
    {
        $domain = $this->option('domain');
        $businessUnit = $this->option('business-unit');
        $framework = $this->option('framework');

        if ($domain) {
            DashboardCacheKey::invalidateDomain($domain, $businessUnit, $framework);
            $this->info("Cache invalidated for domain: {$domain}");
        } else {
            DashboardCacheKey::invalidateAll($businessUnit, $framework);
            $this->info('Cache invalidated for all dashboard domains');
        }

        Log::info('Dashboard cache invalidation command executed', [
            'domain' => $domain ?? 'all',
            'business_unit' => $businessUnit,
            'framework' => $framework,
        ]);

        return Command::SUCCESS;
    }
}
