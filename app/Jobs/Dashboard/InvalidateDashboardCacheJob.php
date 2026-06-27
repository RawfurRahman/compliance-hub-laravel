<?php

namespace App\Jobs\Dashboard;

use App\Services\Dashboard\DashboardCacheKey;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InvalidateDashboardCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(
        public ?string $domain = null,
        public ?string $businessUnit = null,
        public ?string $framework = null,
    ) {}

    public function handle(): void
    {
        if ($this->domain) {
            DashboardCacheKey::invalidateDomain($this->domain, $this->businessUnit, $this->framework);
        } else {
            DashboardCacheKey::invalidateAll($this->businessUnit, $this->framework);
        }

        Log::info('Dashboard cache invalidation job completed', [
            'domain' => $this->domain ?? 'all',
            'business_unit' => $this->businessUnit,
            'framework' => $this->framework,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Dashboard cache invalidation job failed', [
            'domain' => $this->domain ?? 'all',
            'business_unit' => $this->businessUnit,
            'framework' => $this->framework,
            'error' => $e->getMessage(),
        ]);
    }
}
