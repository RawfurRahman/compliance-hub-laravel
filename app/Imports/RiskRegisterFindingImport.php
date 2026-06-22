<?php

namespace App\Imports;

/**
 * RiskRegisterFindingImport
 *
 * Reads one sheet of the Risk Register Excel workbook and creates
 * AssessmentFinding rows under the supplied ProjectAssessment.
 *
 * -----------------------------------------------------------------------
 * COLUMN → AssessmentFinding FIELD MAPPING
 * -----------------------------------------------------------------------
 * Excel column (fuzzy-matched)          DB column           Notes
 * ------------------------------------  ------------------  -------------------------
 * Inherent Risk Rating                  risk_rating         Normalised to High/Medium/Low/None
 * Implementation Status                 status              Normalised to Open/In Progress/Closed
 * Asset/Process + Threat + Vulnerability observation        Concatenated with labels
 * Existing Control + Proposed Control   recommendation      Concatenated with labels
 * Impact C/I/A                          impact              Stored verbatim
 * Follow-up Note                        gap_description     Stored verbatim
 * Risk Acceptance Status == "Accepted"  is_compliant        true when accepted
 * (no direct match)                     framework_control_id Set to the sentinel control
 *                                                           created by the command
 * -----------------------------------------------------------------------
 * Columns read but NOT stored (no matching schema column):
 *   #, Date, Asset Value, Threat Level, Vuln Level, TV, Likelihood,
 *   Residual TV, Residual Likelihood, Residual Risk Rating,
 *   Risk Owner, Communication, Impl From, Impl To
 * -----------------------------------------------------------------------
 */

use App\Models\AssessmentFinding;
use App\Models\FrameworkControl;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class RiskRegisterFindingImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /** @var int  The project_assessment_id to attach findings to */
    private int $assessmentId;

    /** @var int  A sentinel FrameworkControl id used as the required FK */
    private int $sentinelControlId;

    /** @var int  Running count of rows imported */
    public int $imported = 0;

    public function __construct(int $assessmentId, int $sentinelControlId)
    {
        $this->assessmentId      = $assessmentId;
        $this->sentinelControlId = $sentinelControlId;
    }

    // ------------------------------------------------------------------
    // Main import handler
    // ------------------------------------------------------------------

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $data = $row->toArray();

            // Skip rows that have no meaningful content
            $assetProcess = $this->pick($data, ['asset_process', 'assetprocess', 'asset', 'process']);
            $threat       = $this->pick($data, ['threat']);
            if (! $assetProcess && ! $threat) {
                continue;
            }

            // ---- observation (concatenated from three source columns) ----
            $vulnerability = $this->pick($data, ['vulnerability', 'vuln']);
            $observationParts = array_filter([
                $assetProcess ? 'Asset/Process: ' . $assetProcess : null,
                $threat       ? 'Threat: '        . $threat       : null,
                $vulnerability ? 'Vulnerability: ' . $vulnerability : null,
            ]);
            $observation = implode("\n", $observationParts);

            // ---- recommendation (existing + proposed controls) ----------
            $existingControl = $this->pick($data, ['existing_control', 'existingcontrol', 'existing']);
            $proposedControl = $this->pick($data, ['proposed_control', 'proposedcontrol', 'proposed']);
            $recommendationParts = array_filter([
                $existingControl ? 'Existing Control: ' . $existingControl : null,
                $proposedControl ? 'Proposed Control: ' . $proposedControl : null,
            ]);
            $recommendation = implode("\n", $recommendationParts);

            // ---- impact (C/I/A column) ----------------------------------
            $impact = $this->pick($data, [
                'impact_c_i_a', 'impactcia', 'impact_cia', 'impact',
                'impact_c', 'impactc',
            ]);

            // ---- gap_description (follow-up note) ----------------------
            $gapDescription = $this->pick($data, [
                'follow_up_note', 'followupnote', 'followup', 'follow_up',
                'notes', 'note',
            ]);

            // ---- risk_rating (inherent risk rating) --------------------
            $inherentRaw = $this->pick($data, [
                'inherent_risk_rating', 'inherentriskrating', 'inherent_risk',
                'inherentrisk', 'risk_rating', 'riskrating',
            ]);
            $riskRating = $this->normaliseRiskRating($inherentRaw);

            // ---- status (implementation status) ------------------------
            $implStatusRaw = $this->pick($data, [
                'implementation_status', 'implementationstatus',
                'impl_status', 'implstatus', 'status',
            ]);
            $status = $this->normaliseStatus($implStatusRaw);

            // ---- is_compliant (risk acceptance status) -----------------
            $acceptanceRaw = $this->pick($data, [
                'risk_acceptance_status', 'riskacceptancestatus',
                'acceptance_status', 'acceptancestatus', 'acceptance',
            ]);
            $isCompliant = $this->normaliseAcceptance($acceptanceRaw);

            // ---- Persist via Eloquent so booted() hooks fire -----------
            AssessmentFinding::create([
                'project_assessment_id' => $this->assessmentId,
                'framework_control_id'  => $this->sentinelControlId,
                'status'                => $status,
                'risk_rating'           => $riskRating,
                'observation'           => $observation ?: null,
                'gap_description'       => $gapDescription ?: null,
                'impact'                => $impact ?: null,
                'recommendation'        => $recommendation ?: null,
                'is_compliant'          => $isCompliant,
            ]);

            $this->imported++;
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Fuzzy-pick a value from a row by trying multiple normalised key variants.
     * Maatwebsite/Excel normalises heading keys to snake_case lowercase.
     */
    private function pick(array $row, array $candidates): ?string
    {
        foreach ($candidates as $key) {
            // Try exact key first
            if (isset($row[$key]) && $row[$key] !== null && $row[$key] !== '') {
                return trim((string) $row[$key]);
            }
            // Try stripping all non-alphanumeric chars for a looser match
            $stripped = preg_replace('/[^a-z0-9]/', '', strtolower($key));
            foreach ($row as $rowKey => $value) {
                $rowStripped = preg_replace('/[^a-z0-9]/', '', strtolower((string) $rowKey));
                if ($rowStripped === $stripped && $value !== null && $value !== '') {
                    return trim((string) $value);
                }
            }
        }
        return null;
    }

    /**
     * Normalise free-text inherent risk rating to the enum values accepted
     * by the assessment_findings.risk_rating column: High | Medium | Low | None
     */
    private function normaliseRiskRating(?string $raw): string
    {
        if (! $raw) {
            return 'None';
        }
        $lower = strtolower(trim($raw));
        if (str_contains($lower, 'high') || str_contains($lower, 'critical')) {
            return 'High';
        }
        if (str_contains($lower, 'med') || str_contains($lower, 'moderate')) {
            return 'Medium';
        }
        if (str_contains($lower, 'low') || str_contains($lower, 'minor')) {
            return 'Low';
        }
        return 'None';
    }

    /**
     * Normalise implementation status to the enum values accepted by
     * assessment_findings.status: Open | In Progress | Closed
     */
    private function normaliseStatus(?string $raw): string
    {
        if (! $raw) {
            return 'Open';
        }
        $lower = strtolower(trim($raw));
        if (
            str_contains($lower, 'complet') ||
            str_contains($lower, 'done') ||
            str_contains($lower, 'closed') ||
            str_contains($lower, 'implemented') ||
            str_contains($lower, 'resolved')
        ) {
            return 'Closed';
        }
        if (
            str_contains($lower, 'progress') ||
            str_contains($lower, 'ongoing') ||
            str_contains($lower, 'partial') ||
            str_contains($lower, 'in-progress')
        ) {
            return 'In Progress';
        }
        return 'Open';
    }

    /**
     * Treat "Accepted" risk acceptance as compliant (risk accepted = no gap
     * action required); everything else is non-compliant.
     */
    private function normaliseAcceptance(?string $raw): bool
    {
        if (! $raw) {
            return false;
        }
        $lower = strtolower(trim($raw));
        return str_contains($lower, 'accept');
    }
}
