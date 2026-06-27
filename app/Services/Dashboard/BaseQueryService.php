<?php

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\DashboardFilter;
use App\Models\DashboardSnapshot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class BaseQueryService
{
    abstract protected function domain(): string;

    abstract protected function compute(DashboardFilter $filter): array;

    public function get(DashboardFilter $filter, int $cacheTtl = 300): array
    {
        $cacheKey = DashboardCacheKey::forDomain($this->domain(), $filter);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($filter) {
            $start = microtime(true);

            $result = $this->compute($filter);

            $elapsed = round((microtime(true) - $start) * 1000, 2);
            Log::info('Dashboard query computed', [
                'domain' => $this->domain(),
                'business_unit' => $filter->businessUnit,
                'framework' => $filter->framework,
                'duration_ms' => $elapsed,
            ]);

            return $result;
        });
    }

    public function snapshot(DashboardFilter $filter, string $dateScope = 'daily'): DashboardSnapshot
    {
        $start = microtime(true);
        $data = $this->compute($filter);
        $elapsed = round((microtime(true) - $start) * 1000, 2);

        $snapshot = DashboardSnapshot::updateOrCreate(
            [
                'domain' => $this->domain(),
                'business_unit' => $filter->businessUnit,
                'framework' => $filter->framework,
                'date_scope' => $dateScope,
                'snapshot_date' => now()->toDateString(),
            ],
            [
                'snapshot_data' => $data,
                'metadata' => [
                    'filter' => $filter->toArray(),
                    'computation_time_ms' => $elapsed,
                    'snapshot_reason' => 'scheduled',
                ],
                'snapped_at' => now(),
            ]
        );

        Log::info('Dashboard snapshot saved', [
            'domain' => $this->domain(),
            'business_unit' => $filter->businessUnit,
            'framework' => $filter->framework,
            'date_scope' => $dateScope,
            'duration_ms' => $elapsed,
            'snapshot_id' => $snapshot->id,
        ]);

        return $snapshot;
    }

    public function latest(DashboardFilter $filter, string $dateScope = 'daily'): ?array
    {
        $snapshot = DashboardSnapshot::where('domain', $this->domain())
            ->where('business_unit', $filter->businessUnit)
            ->where('framework', $filter->framework)
            ->where('date_scope', $dateScope)
            ->where('snapshot_date', now()->toDateString())
            ->first();

        return $snapshot?->snapshot_data;
    }

    public function history(DashboardFilter $filter, int $days = 30, string $dateScope = 'daily'): array
    {
        return DashboardSnapshot::where('domain', $this->domain())
            ->where('business_unit', $filter->businessUnit)
            ->where('framework', $filter->framework)
            ->where('date_scope', $dateScope)
            ->where('snapshot_date', '>=', now()->subDays($days)->toDateString())
            ->orderBy('snapshot_date')
            ->get()
            ->toArray();
    }
}
