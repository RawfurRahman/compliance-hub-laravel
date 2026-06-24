<?php

namespace App\Modules\Compliance\Services;

use App\Models\AssessmentFinding;
use App\Modules\Compliance\Events\ComplianceStateChanged;
use Illuminate\Support\Collection;

class ComplianceFindingService
{
    public function setState(AssessmentFinding $finding, string $newState): AssessmentFinding
    {
        $oldState = $finding->compliance_state;

        $finding->update(['compliance_state' => $newState]);

        event(new ComplianceStateChanged($finding, $oldState, $newState));

        return $finding;
    }

    public function getOverdue(?int $projectId = null): Collection
    {
        $query = AssessmentFinding::whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->where('status', '!=', 'Closed');

        if ($projectId) {
            $query->whereHas('riskRegister', fn ($q) => $q->where('project_id', $projectId));
        }

        return $query->with('frameworkControl', 'riskRegister')->get();
    }

    public function getByFramework(int $frameworkControlId): Collection
    {
        return AssessmentFinding::where('framework_control_id', $frameworkControlId)
            ->with('riskRegister')
            ->latest()
            ->get();
    }

    public function getByProject(int $projectId, ?string $state = null): Collection
    {
        $query = AssessmentFinding::whereHas('riskRegister', fn ($q) => $q->where('project_id', $projectId));

        if ($state) {
            $query->where('compliance_state', $state);
        }

        return $query->with('frameworkControl', 'source')->latest()->get();
    }

    public static function countByState(int $projectId): array
    {
        $findings = AssessmentFinding::whereHas('riskRegister', fn ($q) => $q->where('project_id', $projectId))
            ->selectRaw("COALESCE(compliance_state, 'unknown') as state, COUNT(*) as count")
            ->groupBy('state')
            ->pluck('count', 'state')
            ->toArray();

        return [
            'compliant' => $findings['compliant'] ?? 0,
            'partially_compliant' => $findings['partially_compliant'] ?? 0,
            'non_compliant' => $findings['non_compliant'] ?? 0,
            'overdue' => $findings['overdue'] ?? 0,
            'waived' => $findings['waived'] ?? 0,
            'under_review' => $findings['under_review'] ?? 0,
        ];
    }
}
