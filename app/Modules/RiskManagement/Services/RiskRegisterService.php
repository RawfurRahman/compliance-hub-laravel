<?php

namespace App\Modules\RiskManagement\Services;

use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskHeatmapSnapshot;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Asset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * RiskRegisterService — Phase 3 / 5 / 7 / 8
 *
 * Owns all business logic for the Risk Management module.
 * Consumes RiskCalculationService for all scoring.
 */
class RiskRegisterService
{
    public function __construct(
        private RiskCalculationService $calc
    ) {}

    public const LIKELIHOOD_AXIS = RiskRegister::LIKELIHOOD_AXIS;
    public const IMPACT_AXIS     = RiskRegister::IMPACT_AXIS;

    /* ------------------------------------------------------------------ *
     *  Risk Register — list + CRUD
     * ------------------------------------------------------------------ */

    public function riskRegisterForProject(int $projectId): Collection
    {
        return RiskRegister::with(['asset', 'ownerUser', 'createdBy', 'updatedBy', 'frameworkControl'])
            ->where('project_id', $projectId)
            ->orderByDesc('risk_rating_avtvlh')
            ->orderBy('serial_no')
            ->get();
    }

    public function generateRiskId(int $projectId): string
    {
        $last = RiskRegister::withTrashed()
            ->where('project_id', $projectId)
            ->get()
            ->max(fn($r) => (int)$r->serial_no);

        return (string)($last + 1);
    }

    public function upsertEntry(array $data, ?int $id = null): RiskRegister
    {
        // Stamp calculated fields
        $this->calc->stampCalculations($data);

        if ($id) {
            $risk = RiskRegister::findOrFail($id);
            $oldStatus = $risk->implementation_status;
            $data['updated_by'] = Auth::id();
            
            // Normalize JSON input fields
            if (isset($data['threats']) && is_string($data['threats'])) {
                $data['threats'] = json_decode($data['threats'], true) ?: [$data['threats']];
            }
            if (isset($data['vulnerabilities']) && is_string($data['vulnerabilities'])) {
                $data['vulnerabilities'] = json_decode($data['vulnerabilities'], true) ?: [$data['vulnerabilities']];
            }
            if (isset($data['evidence_ids']) && is_string($data['evidence_ids'])) {
                $data['evidence_ids'] = json_decode($data['evidence_ids'], true) ?: [];
            }
            
            $risk->update($data);

            // Log activity
            $this->logActivity('risk_updated', "Updated risk record serial_no: {$risk->serial_no}", [
                'risk_id' => $risk->id,
                'serial_no' => $risk->serial_no,
                'changes' => $risk->getChanges(),
            ]);

            return $risk->fresh();
        }

        if (empty($data['serial_no'])) {
            $data['serial_no'] = $this->generateRiskId($data['project_id']);
        }
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        // Normalize JSON input fields
        if (isset($data['threats']) && is_string($data['threats'])) {
            $data['threats'] = json_decode($data['threats'], true) ?: [$data['threats']];
        }
        if (isset($data['vulnerabilities']) && is_string($data['vulnerabilities'])) {
            $data['vulnerabilities'] = json_decode($data['vulnerabilities'], true) ?: [$data['vulnerabilities']];
        }
        if (isset($data['evidence_ids']) && is_string($data['evidence_ids'])) {
            $data['evidence_ids'] = json_decode($data['evidence_ids'], true) ?: [];
        }

        $risk = RiskRegister::create($data);

        // Log activity
        $this->logActivity('risk_created', "Created risk record serial_no: {$risk->serial_no}", [
            'risk_id' => $risk->id,
            'serial_no' => $risk->serial_no,
        ]);

        return $risk;
    }

    public function deleteEntry(int $id): void
    {
        $risk = RiskRegister::findOrFail($id);
        $serialNo = $risk->serial_no;
        $risk->delete();

        // Log activity
        $this->logActivity('risk_deleted', "Soft-deleted risk record serial_no: {$serialNo}", [
            'risk_id' => $id,
            'serial_no' => $serialNo,
        ]);
    }

    public function transitionStatus(RiskRegister $risk, string $newStatus, ?string $reason = null): void
    {
        $old = $risk->implementation_status;
        $risk->update(['implementation_status' => $newStatus, 'updated_by' => Auth::id()]);
        
        // Log activity
        $this->logActivity('risk_status_changed', "Status of risk serial_no: {$risk->serial_no} changed from {$old} to {$newStatus}", [
            'risk_id' => $risk->id,
            'from_status' => $old,
            'to_status' => $newStatus,
            'reason' => $reason,
        ]);
    }

    public function transitionLifecycle(RiskRegister $risk, string $newStatus, ?string $reason = null): RiskRegister
    {
        if (!$risk->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from '{$risk->lifecycle_status}' to '{$newStatus}'."
            );
        }

        $oldStatus = $risk->lifecycle_status;
        $risk->update([
            'lifecycle_status' => $newStatus,
            'updated_by' => Auth::id(),
        ]);

        $this->logActivity('risk_lifecycle_changed', "Risk lifecycle changed from {$oldStatus} to {$newStatus}", [
            'risk_id' => $risk->id,
            'serial_no' => $risk->serial_no,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'reason' => $reason,
        ]);

        \App\Modules\RiskManagement\Events\RiskLifecycleChanged::dispatch($risk, $oldStatus, $newStatus, $reason);

        return $risk->fresh();
    }

    private function logActivity(string $action, string $description, ?array $details = null): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
    }

    /* ------------------------------------------------------------------ *
     *  KPIs
     * ------------------------------------------------------------------ */

    public function kpis(int $projectId): array
    {
        $base     = RiskRegister::where('project_id', $projectId);
        $total    = (clone $base)->count();
        
        // Count using ranges mapping to Critical, High, Medium, Low
        $critical = (clone $base)->where('risk_rating_avtvlh', '>=', 128)->count();
        $high     = (clone $base)->where('risk_rating_avtvlh', '>=', 84)->where('risk_rating_avtvlh', '<', 128)->count();
        $medium   = (clone $base)->where('risk_rating_avtvlh', '>=', 54)->where('risk_rating_avtvlh', '<', 84)->count();
        $low      = (clone $base)->where('risk_rating_avtvlh', '<', 54)->count();
        
        $open     = (clone $base)->whereNotIn('implementation_status', ['Completed'])->count();
        $accepted = (clone $base)->where('measurement', 'Accepted')->count();
        $notAcc   = (clone $base)->where('measurement', 'Not Accepted')->count();
        $mitigated= (clone $base)->where('implementation_status', 'Completed')->count();
        $closed   = (clone $base)->where('implementation_status', 'Completed')->count();
        
        $overdueReview = 0; // next_review_date removed from schema

        $avgInherent = $total > 0 ? round((clone $base)->avg('risk_rating_avtvlh'), 1) : 0;
        $avgResidual = $total > 0 ? round((clone $base)->avg('residual_rating'), 1)  : 0;
        $controlEff  = $avgInherent > 0
            ? $this->calc->controlEffectiveness((int)$avgInherent, (int)$avgResidual)
            : 0.0;

        return compact(
            'total', 'critical', 'high', 'medium', 'low',
            'open', 'accepted', 'notAcc', 'mitigated', 'closed',
            'overdueReview', 'avgInherent', 'avgResidual', 'controlEff'
        );
    }

    /* ------------------------------------------------------------------ *
     *  Heat Map
     * ------------------------------------------------------------------ */

    public function heatmapMatrix(int $projectId, string $type = 'inherent'): array
    {
        $risks = RiskRegister::where('project_id', $projectId)->get();
        
        $matrix = [];
        foreach (array_keys(self::LIKELIHOOD_AXIS) as $l) {
            foreach (array_keys(self::IMPACT_AXIS) as $i) {
                $matrix[$l][$i] = 0;
            }
        }

        foreach ($risks as $risk) {
            if ($type === 'residual') {
                $l = $risk->residual_lh;
                $i = $risk->residual_tv; // W column acts as residual impact
            } else {
                $l = $risk->likelihood_lh;
                $i = max($risk->impact_confidentiality, $risk->impact_integrity, $risk->impact_availability);
            }

            if (isset($matrix[$l][$i])) {
                $matrix[$l][$i]++;
            }
        }

        return $matrix;
    }

    public function heatmapCells(int $projectId, string $type = 'inherent'): array
    {
        $matrix = $this->heatmapMatrix($projectId, $type);
        $cells  = [];

        foreach (array_keys(self::LIKELIHOOD_AXIS) as $l) {
            foreach (array_keys(self::IMPACT_AXIS) as $i) {
                $score = $l * $i;
                $level = RiskRegister::scoreToLevel($score);
                $cells[] = [
                    'likelihood' => $l,
                    'impact'     => $i,
                    'count'      => $matrix[$l][$i],
                    'score'      => $score,
                    'level'      => $level,
                ];
            }
        }
        return $cells;
    }

    /** Persist a snapshot for trend analysis */
    public function snapshotHeatmap(int $projectId, string $type = 'inherent'): RiskHeatmapSnapshot
    {
        $matrix = $this->heatmapMatrix($projectId, $type);
        $base   = RiskRegister::where('project_id', $projectId);

        // Count levels based on rating score ranges
        $critical = $type === 'residual' 
            ? (clone $base)->where('residual_rating', '>=', 128)->count()
            : (clone $base)->where('risk_rating_avtvlh', '>=', 128)->count();

        $high = $type === 'residual'
            ? (clone $base)->where('residual_rating', '>=', 84)->where('residual_rating', '<', 128)->count()
            : (clone $base)->where('risk_rating_avtvlh', '>=', 84)->where('risk_rating_avtvlh', '<', 128)->count();

        $medium = $type === 'residual'
            ? (clone $base)->where('residual_rating', '>=', 54)->where('residual_rating', '<', 84)->count()
            : (clone $base)->where('risk_rating_avtvlh', '>=', 54)->where('risk_rating_avtvlh', '<', 84)->count();

        $low = $type === 'residual'
            ? (clone $base)->where('residual_rating', '<', 54)->count()
            : (clone $base)->where('risk_rating_avtvlh', '<', 54)->count();

        return RiskHeatmapSnapshot::create([
            'project_id'     => $projectId,
            'snapshot_type'  => $type,
            'matrix_data'    => $matrix,
            'total_risks'    => (clone $base)->count(),
            'critical_count' => $critical,
            'high_count'     => $high,
            'medium_count'   => $medium,
            'low_count'      => $low,
            'snapped_at'     => now(),
        ]);
    }

    /* ------------------------------------------------------------------ *
     *  Dashboard widget — top open risks (used by DashboardMetricsService)
     * ------------------------------------------------------------------ */

    public function topOpenRisks(int $limit = 5): Collection
    {
        return RiskRegister::with(['project', 'frameworkControl'])
            ->where('risk_rating_avtvlh', '>=', 84) // Critical and High ratings
            ->whereNotIn('implementation_status', ['Completed'])
            ->orderByDesc('risk_rating_avtvlh')
            ->take($limit)
            ->get()
            ->map(fn ($r) => [
                'id'          => $r->id,
                'risk_id'     => $r->serial_no,
                'name'        => $r->asset_process_service,
                'level'       => $r->inherent_risk_level,
                'score'       => $r->risk_rating_avtvlh,
                'owner'       => $r->risk_owner,
                'project'     => $r->project?->name ?? '',
                'control'     => $r->frameworkControl?->control_id ?? '',
                'status'      => $r->implementation_status,
                'treatment'   => $r->measurement,
            ]);
    }

    /* ------------------------------------------------------------------ *
     *  Export
     * ------------------------------------------------------------------ */

    public function exportRegisterData(int $projectId): Collection
    {
        return RiskRegister::with(['asset', 'ownerUser', 'frameworkControl'])
            ->where('project_id', $projectId)
            ->orderBy('serial_no')
            ->get();
    }

    /* ------------------------------------------------------------------ *
     *  Control Mapping (Phase 7)
     * ------------------------------------------------------------------ */

    public function mapControl(int $riskId, int $frameworkControlId, ?int $controlId = null, ?string $notes = null): void
    {
        \App\Modules\RiskManagement\Models\RiskControlMapping::updateOrCreate(
            ['risk_register_id' => $riskId, 'framework_control_id' => $frameworkControlId],
            ['control_id' => $controlId, 'notes' => $notes]
        );
    }

    public function unmapControl(int $riskId, int $frameworkControlId): void
    {
        \App\Modules\RiskManagement\Models\RiskControlMapping::where('risk_register_id', $riskId)
            ->where('framework_control_id', $frameworkControlId)
            ->delete();
    }
}
