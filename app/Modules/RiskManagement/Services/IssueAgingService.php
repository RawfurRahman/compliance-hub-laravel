<?php

namespace App\Modules\RiskManagement\Services;

use App\DTOs\Dashboard\DashboardFilter;
use App\Models\AssessmentFinding;
use App\Modules\Compliance\Models\AuditFinding;
use App\Modules\RiskManagement\Models\RiskTreatmentPlan;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class IssueAgingService
{
    private const AGING_BUCKETS = [
        '0-7' => 7,
        '8-30' => 30,
        '31-60' => 60,
        '61-90' => 90,
        '90+' => PHP_INT_MAX,
    ];

    public function agingBuckets(DashboardFilter $filter): array
    {
        $assessmentAging = $this->bucketFindings($this->assessmentFindingsQuery($filter));
        $auditAging = $this->bucketFindings($this->auditFindingsQuery($filter));
        $treatmentAging = $this->bucketTreatmentPlans($filter);

        $merged = array_fill_keys(array_keys(self::AGING_BUCKETS), 0);
        foreach ([$assessmentAging, $auditAging, $treatmentAging] as $buckets) {
            foreach ($buckets as $label => $count) {
                $merged[$label] += $count;
            }
        }

        return [
            'buckets' => $merged,
            'total_aging' => array_sum($merged),
            'source_breakdown' => [
                'assessment_findings' => $assessmentAging,
                'audit_findings' => $auditAging,
                'treatment_plans' => $treatmentAging,
            ],
        ];
    }

    private function bucketFindings(Collection $items): array
    {
        $buckets = array_fill_keys(array_keys(self::AGING_BUCKETS), 0);
        $now = Carbon::now();

        foreach ($items as $item) {
            $ageDays = Carbon::parse($item->created_at)->diffInDays($now);
            foreach (self::AGING_BUCKETS as $label => $upper) {
                if ($ageDays <= $upper) {
                    $buckets[$label]++;
                    break;
                }
            }
        }

        return $buckets;
    }

    private function bucketTreatmentPlans(DashboardFilter $filter): array
    {
        $query = RiskTreatmentPlan::query()->whereNotIn('status', ['completed', 'cancelled']);

        if ($filter->owner) {
            $query->where('responsible_party', 'LIKE', "%{$filter->owner}%");
        }
        if ($filter->projectId) {
            $query->whereHas('risk', fn ($q) => $q->where('project_id', $filter->projectId));
        }

        return $this->bucketFindings($query->get());
    }

    private function assessmentFindingsQuery(DashboardFilter $filter): Collection
    {
        $query = AssessmentFinding::query()->where('is_compliant', false);

        if ($filter->framework) {
            $query->whereHas('projectAssessment.framework', fn ($q) => $q->where('name', $filter->framework));
        }
        if ($filter->owner) {
            $query->whereHas('frameworkControl', fn ($q) => $q->where('domain', $filter->owner));
        }
        if ($filter->riskStatus) {
            $query->where('risk_rating', $filter->riskStatus);
        }
        if ($filter->projectId) {
            $query->whereHas('projectAssessment', fn ($q) => $q->where('project_id', $filter->projectId));
        }

        return $query->get();
    }

    private function auditFindingsQuery(DashboardFilter $filter): Collection
    {
        $query = AuditFinding::query()->whereIn('status', ['open', 'in_review']);

        if ($filter->framework) {
            $query->whereHas('frameworkControl.framework', fn ($q) => $q->where('name', $filter->framework));
        }
        if ($filter->projectId) {
            $query->where('project_id', $filter->projectId);
        }

        return $query->get();
    }
}
