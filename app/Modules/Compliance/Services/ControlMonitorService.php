<?php

namespace App\Modules\Compliance\Services;

use App\Models\AssessmentFinding;
use App\Modules\Compliance\Models\ControlMonitor;
use App\Modules\Compliance\Models\MonitoringRule;
use Illuminate\Support\Collection;

class ControlMonitorService
{
    public function runCheck(ControlMonitor $monitor): AssessmentFinding
    {
        $rule = $monitor->monitoringRule;
        $result = $this->evaluateRule($rule);

        $finding = $this->createFindingFromMonitor($monitor, $rule, $result);

        $monitor->update([
            'last_run_at' => now(),
            'last_result' => $result,
            'last_finding_id' => $finding->id,
            'consecutive_failures' => $result === 'fail'
                ? $monitor->consecutive_failures + 1
                : 0,
            'next_run_at' => $this->calculateNextRun($rule),
        ]);

        return $finding;
    }

    public function runAllDue(): int
    {
        $monitors = ControlMonitor::due()->with('monitoringRule')->get();
        $count = 0;

        foreach ($monitors as $monitor) {
            $this->runCheck($monitor);
            $count++;
        }

        return $count;
    }

    public function getDueMonitors(): Collection
    {
        return ControlMonitor::due()->with('monitoringRule', 'control')->get();
    }

    protected function evaluateRule(MonitoringRule $rule): string
    {
        if (!$rule->check_expression) {
            return 'pass';
        }

        try {
            $result = eval('return ' . $rule->check_expression . ';');
            return $result ? 'pass' : 'fail';
        } catch (\Throwable) {
            return 'error';
        }
    }

    protected function createFindingFromMonitor(ControlMonitor $monitor, MonitoringRule $rule, string $result): AssessmentFinding
    {
        $finding = AssessmentFinding::create([
            'source_type' => get_class($monitor),
            'source_id' => $monitor->id,
            'compliance_state' => $result === 'pass' ? 'compliant' : 'non_compliant',
            'status' => $result === 'pass' ? 'Closed' : 'Open',
            'is_compliant' => $result === 'pass',
            'is_applicable' => true,
            'observation' => sprintf('Monitoring: %s — %s', $rule->name, $result === 'pass' ? 'passed' : 'failed'),
        ]);

        if ($monitor->control_id) {
            $finding->framework_control_id = $monitor->control->riskControlMappings()->first()?->framework_control_id;
            $finding->save();
        }

        return $finding;
    }

    protected function calculateNextRun(MonitoringRule $rule): ?\DateTime
    {
        if (!$rule->schedule_cron) {
            return null;
        }

        $intervals = [
            '* * * * *' => 1,
            '*/5 * * * *' => 5,
            '*/15 * * * *' => 15,
            '*/30 * * * *' => 30,
            '0 * * * *' => 60,
            '0 */2 * * *' => 120,
            '0 */4 * * *' => 240,
            '0 */6 * * *' => 360,
            '0 */8 * * *' => 480,
            '0 */12 * * *' => 720,
            '0 0 * * *' => 1440,
            '0 0 * * 0' => 10080,
            '0 0 1 * *' => 43200,
        ];

        $minutes = $intervals[$rule->schedule_cron] ?? 1440;

        return now()->addMinutes($minutes);
    }
}
