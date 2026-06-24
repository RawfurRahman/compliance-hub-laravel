<?php

namespace App\Modules\Compliance\Services;

use App\Models\AssessmentFinding;
use App\Models\Framework;
use App\Modules\Compliance\Models\ControlTest;
use App\Modules\Compliance\Models\FrameworkControlMap;
use App\Modules\RiskManagement\Models\RiskTreatmentPlan;
use Illuminate\Support\Collection;

class ComplianceQueryService
{
    public function controlsFailedThisWeek(int $projectId): Collection
    {
        $weekStart = now()->startOfWeek();

        return ControlTest::failed()
            ->where('test_date', '>=', $weekStart)
            ->whereHas('control.riskControlMappings.risk', fn ($q) => $q->where('project_id', $projectId))
            ->with('control')
            ->get()
            ->groupBy('control_id')
            ->map(fn ($tests, $controlId) => [
                'control_id' => $controlId,
                'control_name' => $tests->first()->control->name,
                'control_code' => $tests->first()->control->control_code,
                'fail_count' => $tests->count(),
                'latest_test' => $tests->first()->test_date,
            ])
            ->values();
    }

    public function frameworkRequirementsImpacted(int $frameworkControlId): Collection
    {
        return AssessmentFinding::where('framework_control_id', $frameworkControlId)
            ->whereIn('compliance_state', ['non_compliant', 'partially_compliant'])
            ->with('riskRegister.project', 'source')
            ->latest()
            ->get();
    }

    public function overdueBySLA(int $projectId): Collection
    {
        return RiskTreatmentPlan::overdue()
            ->whereHas('risk', fn ($q) => $q->where('project_id', $projectId))
            ->with('risk')
            ->latest()
            ->get();
    }

    public function complianceByFramework(int $projectId): Collection
    {
        $findings = AssessmentFinding::whereHas('riskRegister', fn ($q) => $q->where('project_id', $projectId))
            ->whereNotNull('framework_control_id')
            ->with('frameworkControl.framework')
            ->get();

        return $findings->groupBy('frameworkControl.framework.name')
            ->map(fn ($items, $framework) => [
                'framework' => $framework,
                'total' => $items->count(),
                'pass' => $items->where('is_compliant', true)->count(),
                'fail' => $items->where('is_compliant', false)->count(),
                'compliance_pct' => $items->count() > 0
                    ? round($items->where('is_compliant', true)->count() / $items->count() * 100, 1)
                    : 0,
            ])
            ->values();
    }

    public function controlTestHistory(int $controlId, int $limit = 10): Collection
    {
        return ControlTest::where('control_id', $controlId)
            ->with('testedBy', 'assessmentFinding')
            ->latest()
            ->take($limit)
            ->get();
    }

    public function unbakedMappings(?int $frameworkId = null): Collection
    {
        $query = FrameworkControlMap::doesntHave('frameworkVersion');

        if ($frameworkId) {
            $query->whereHas('frameworkControl', fn ($q) => $q->where('framework_id', $frameworkId));
        }

        return $query->with('control', 'frameworkControl.framework')->get();
    }
}
