<?php

namespace App\Modules\RiskManagement\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\AssessmentFinding;
use App\Modules\RiskManagement\Imports\RiskRegisterFindingImport;
use App\Modules\RiskManagement\Imports\RiskRegisterFindingMultiImport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ImportRiskRegisterFindings
 *
 * Reads a Risk Register Excel workbook, groups the findings on the "Risk Register"
 * sheet by department/Risk Owner, and creates ProjectAssessment (type=Gap) rows
 * and corresponding AssessmentFinding rows.
 *
 * Usage:
 *   php artisan import:risk-register \
 *       --project-id=1 \
 *       --file=storage/app/imports/risk_register.xlsx
 */
class ImportRiskRegisterFindings extends Command
{
    protected $signature = 'import:risk-register
        {--project-id= : ID of the project to attach assessments to}
        {--file=       : Path to the Risk Register Excel file}
        {--fresh       : Delete existing Risk Register findings before importing}';

    protected $description = 'Import Risk Register Excel findings into AssessmentFinding records';

    public function handle(): int
    {
        // ----------------------------------------------------------------
        // 1. Resolve the Excel file path
        // ----------------------------------------------------------------
        $filePath = $this->option('file')
            ?: storage_path('app/imports/risk_register.xlsx');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        // ----------------------------------------------------------------
        // 2. Ensure the "Risk Register" framework exists
        // ----------------------------------------------------------------
        $framework = Framework::firstOrCreate(
            ['slug' => 'risk_register'],
            [
                'name'        => 'Risk Register',
                'version'     => 'current',
                'description' => 'Internal Risk Register — imported from Excel',
                'is_active'   => true,
            ]
        );
        $this->info("Framework: {$framework->name} (id={$framework->id})");

        // ----------------------------------------------------------------
        // 3. Resolve or create the Project
        // ----------------------------------------------------------------
        $projectId = $this->option('project-id');
        if ($projectId) {
            $project = Project::findOrFail((int) $projectId);
        } else {
            // Find the first admin user to satisfy the NOT NULL user_id FK
            $adminUserId = DB::table('users')->value('id');
            if (! $adminUserId) {
                $this->error('No users found in the database. Create at least one user first.');
                return self::FAILURE;
            }
            $project = Project::firstOrCreate(
                ['name' => 'Risk Register Import', 'module_type' => 'risk_register'],
                ['user_id' => $adminUserId]
            );
        }
        $this->info("Project: {$project->name} (id={$project->id})");

        // ----------------------------------------------------------------
        // 4. Ensure a sentinel FrameworkControl exists
        // ----------------------------------------------------------------
        $sentinelControl = FrameworkControl::firstOrCreate(
            [
                'framework_id' => $framework->id,
                'control_id'   => 'RR-GENERAL',
            ],
            [
                'domain'                  => 'Risk Register',
                'requirement_description' => 'General risk register entry (sentinel control)',
                'required_evidence'       => null,
            ]
        );
        $this->info("Sentinel control id={$sentinelControl->id}");

        // ----------------------------------------------------------------
        // 5. Load and process the Risk Register sheet
        // ----------------------------------------------------------------
        $importer = new RiskRegisterFindingImport();
        $this->line("Loading sheet 'Risk Register'...");

        try {
            Excel::import(new RiskRegisterFindingMultiImport($importer), $filePath);
        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
            return self::FAILURE;
        }

        $rows = $importer->rows;
        $this->info("Found " . $rows->count() . " total rows in sheet.");

        // Group by Risk Owner
        $grouped = $rows->groupBy(function ($row) {
            $owner = $this->pick($row->toArray(), ['risk_owner', 'riskowner', 'owner']);
            return $owner ? trim($owner) : 'Unknown';
        });

        $totalImported = 0;
        $assessmentsCreated = 0;

        foreach ($grouped as $departmentRaw => $deptRows) {
            if ($departmentRaw === 'Unknown') {
                continue;
            }

            // Normalize department name to clean short format
            $department = trim($departmentRaw);
            if (stripos($department, 'IT') === 0) $department = 'IT';
            elseif (stripos($department, 'HR') === 0) $department = 'HR';
            elseif (stripos($department, 'Compliance') === 0) $department = 'Compliance';
            elseif (stripos($department, 'Procurement') === 0) $department = 'Procurement';
            elseif (stripos($department, 'Facilities') === 0) $department = 'Facilities';
            elseif (stripos($department, 'Marketing') === 0) $department = 'Marketing';
            elseif (stripos($department, 'CISO') === 0) $department = 'CISO';

            $this->line("\nProcessing department: {$department} (Raw: {$departmentRaw})");

            $assessment = ProjectAssessment::firstOrCreate(
                [
                    'project_id'   => $project->id,
                    'framework_id' => $framework->id,
                    'type'         => 'Gap',
                    'overall_status' => 'Risk Register — ' . $department,
                ],
                [
                    'start_date' => now(),
                    'end_date'   => now()->addYear(),
                ]
            );

            if ($assessment->wasRecentlyCreated) {
                $assessmentsCreated++;
            }
            $this->line("  Assessment id={$assessment->id} (" . ($assessment->wasRecentlyCreated ? 'created' : 'found') . ")");

            if ($this->option('fresh')) {
                $deleted = $assessment->findings()->delete();
                $this->line("  --fresh: deleted {$deleted} existing findings.");
            }

            foreach ($deptRows as $row) {
                $data = $row->toArray();

                // Skip rows that have no meaningful content
                $assetProcess = $this->pick($data, ['asset_process_service', 'assetprocessservice', 'asset_process', 'assetprocess', 'asset', 'process']);
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
                $impactC = $this->pick($data, ['impact_c', 'impactc']);
                $impactI = $this->pick($data, ['impact_i', 'impacti']);
                $impactA = $this->pick($data, ['impact_a', 'impacta']);
                $impactParts = [];
                if ($impactC) $impactParts[] = 'C: ' . $impactC;
                if ($impactI) $impactParts[] = 'I: ' . $impactI;
                if ($impactA) $impactParts[] = 'A: ' . $impactA;
                $impact = implode(', ', $impactParts);

                // ---- gap_description (follow-up note) ----------------------
                $gapDescription = $this->pick($data, [
                    'follow_up_note', 'followupnote', 'followup', 'follow_up',
                    'notes', 'note',
                ]);

                // ---- risk_rating (inherent risk rating) --------------------
                $inherentRaw = $this->pick($data, [
                    'inherent_risk_rating', 'inherentriskrating', 'inherent_risk',
                    'inherentrisk', 'risk_rating', 'riskrating', 'risk_rating_avtvlh', 'riskratingavtvlh',
                ]);
                $riskRating = $this->normaliseRiskRating($inherentRaw);

                // ---- status (implementation status) ------------------------
                $implStatusRaw = $this->pick($data, [
                    'implementation_status', 'implementationstatus',
                    'impl_status', 'implstatus', 'status', 'impl_status',
                ]);
                $status = $this->normaliseStatus($implStatusRaw);

                // ---- is_compliant (risk acceptance status) -----------------
                $acceptanceRaw = $this->pick($data, [
                    'risk_acceptance_status', 'riskacceptancestatus',
                    'acceptance_status', 'acceptancestatus', 'acceptance', 'measurement',
                ]);
                $isCompliant = $this->normaliseAcceptance($acceptanceRaw);

                // ---- Persist via Eloquent so booted() hooks fire -----------
                AssessmentFinding::create([
                    'project_assessment_id' => $assessment->id,
                    'framework_control_id'  => $sentinelControl->id,
                    'status'                => $status,
                    'risk_rating'           => $riskRating,
                    'observation'           => $observation ?: null,
                    'gap_description'       => $gapDescription ?: null,
                    'impact'                => $impact ?: null,
                    'recommendation'        => $recommendation ?: null,
                    'is_compliant'          => $isCompliant,
                ]);

                $totalImported++;
            }
        }

        $this->info("\nDone. Total findings imported: {$totalImported}");
        return self::SUCCESS;
    }

    private function pick(array $row, array $candidates): ?string
    {
        foreach ($candidates as $key) {
            if (isset($row[$key]) && $row[$key] !== null && $row[$key] !== '') {
                return trim((string) $row[$key]);
            }
            $stripped = preg_replace('/[^a-z0-9]/', '', strtolower($key));
            foreach ($row as $rowKey => $value) {
                $rowStripped = preg_replace('/[^a-z0-9]/', '', strtolower((string)$rowKey));
                if ($rowStripped === $stripped && $value !== null && $value !== '') {
                    return trim((string) $value);
                }
            }
        }
        return null;
    }

    private function normaliseRiskRating(?string $raw): string
    {
        if (! $raw) {
            return 'None';
        }
        $raw = trim($raw);
        if (is_numeric($raw)) {
            $val = (int)$raw;
            if ($val >= 84) {
                return 'High';
            }
            if ($val >= 54) {
                return 'Medium';
            }
            return 'Low';
        }
        $lower = strtolower($raw);
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

    private function normaliseAcceptance(?string $raw): bool
    {
        if (! $raw) {
            return false;
        }
        $lower = strtolower(trim($raw));
        return str_contains($lower, 'accept') && !str_contains($lower, 'not');
    }
}
