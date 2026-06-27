<?php

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\DashboardFilter;

final class DashboardCacheKey
{
    public static function forDomain(string $domain, DashboardFilter $filter, string $suffix = ''): string
    {
        $parts = [
            'dashboard',
            $domain,
            $filter->businessUnit ?? '_all',
            $filter->framework ?? '_all',
            $filter->cacheKey(),
        ];

        if ($suffix) {
            $parts[] = $suffix;
        }

        return implode(':', $parts);
    }

    public static function snapshotTag(string $domain, ?string $businessUnit = null, ?string $framework = null): string
    {
        return implode(':', array_filter([
            'dashboard_snapshot',
            $domain,
            $businessUnit,
            $framework,
        ]));
    }

    public static function invalidateDomain(string $domain, ?string $businessUnit = null, ?string $framework = null): void
    {
        $tag = self::snapshotTag($domain, $businessUnit, $framework);
        cache()->forget($tag);

        $pattern = 'dashboard:' . $domain . ':*';
        if ($businessUnit) {
            $pattern = 'dashboard:' . $domain . ':' . $businessUnit . ':*';
        }
        if ($framework) {
            $pattern = 'dashboard:' . $domain . ':' . ($businessUnit ?? '*') . ':' . $framework . ':*';
        }

        logger()->info('Dashboard cache invalidated', [
            'domain' => $domain,
            'business_unit' => $businessUnit,
            'framework' => $framework,
            'pattern' => $pattern,
        ]);
    }

    public static function invalidateAll(?string $businessUnit = null, ?string $framework = null): void
    {
        foreach (DashboardDomains::ALL as $domain) {
            self::invalidateDomain($domain, $businessUnit, $framework);
        }

        logger()->info('All dashboard cache invalidated', [
            'business_unit' => $businessUnit,
            'framework' => $framework,
        ]);
    }
}
