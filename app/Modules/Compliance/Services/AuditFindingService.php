<?php

namespace App\Modules\Compliance\Services;

use App\Models\AssessmentFinding;
use App\Modules\Compliance\Models\AuditFinding;
use Illuminate\Support\Collection;

class AuditFindingService
{
    public function create(array $data): AuditFinding
    {
        return AuditFinding::create($data);
    }

    public function schedule(int $auditFindingId, int $auditorId, ?\DateTime $date = null): AuditFinding
    {
        $finding = AuditFinding::findOrFail($auditFindingId);
        $finding->update([
            'auditor_id' => $auditorId,
            'audit_date' => $date ?? now(),
            'status' => 'in_review',
        ]);
        return $finding;
    }

    public function close(int $auditFindingId, ?string $resolution = null): AuditFinding
    {
        $finding = AuditFinding::findOrFail($auditFindingId);
        $finding->update([
            'status' => 'closed',
            'remediation_plan' => $resolution ? ($finding->remediation_plan . "\n\nResolution: " . $resolution) : $finding->remediation_plan,
        ]);
        return $finding;
    }

    public function linkToFinding(int $auditFindingId, int $assessmentFindingId): array
    {
        $auditFinding = AuditFinding::findOrFail($auditFindingId);
        $assessmentFinding = AssessmentFinding::findOrFail($assessmentFindingId);

        $assessmentFinding->update([
            'source_type' => get_class($auditFinding),
            'source_id' => $auditFinding->id,
        ]);

        return ['audit_finding' => $auditFinding, 'assessment_finding' => $assessmentFinding];
    }

    public function getOpenFindings(?int $projectId = null): Collection
    {
        $query = AuditFinding::open();
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        return $query->with('auditor', 'project')->latest()->get();
    }

    public function getByProject(int $projectId): Collection
    {
        return AuditFinding::where('project_id', $projectId)
            ->with('auditor')
            ->latest()
            ->get();
    }
}
