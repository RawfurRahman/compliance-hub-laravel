<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('compliance:send-scheduled-reports')->daily();

Schedule::command('maturity:snapshot')->weekly();

// Dashboard aggregation snapshots
Schedule::command('dashboard:refresh-snapshots --date-scope=daily')->dailyAt('01:00');

// Executive metrics (financial exposure + remediation MTTR/SLA)
Schedule::command('risks:snapshot-executive-metrics')->dailyAt('02:00');

// Scheduled tasks for new metrics tables
Schedule::command('risks:snapshot-financial-metrics')->dailyAt('02:30');
Schedule::command('risks:snapshot-remediation-metrics')->dailyAt('03:00');

// Dashboard aggregation snapshots
Schedule::command('dashboard:refresh-snapshots --date-scope=weekly')->weekly();
Schedule::command('dashboard:refresh-snapshots --date-scope=monthly')->monthly();

// Invalidate cache on data-change hooks (runs every 5 min as safety net)
Schedule::command('dashboard:invalidate-cache')->everyFiveMinutes()->between('06:00', '22:00');

// Compliance monitoring checks (runs every 15 minutes during business hours)
Schedule::command('compliance:run-monitoring-checks')->everyFifteenMinutes()->between('08:00', '18:00');
