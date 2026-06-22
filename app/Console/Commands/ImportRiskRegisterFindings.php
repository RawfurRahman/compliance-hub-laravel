<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Imports\RiskRegisterFindingImport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * ImportRiskRegisterFindings
 *
 * Reads a Risk Register Excel workbook and creates AssessmentFinding rows
 * via Eloquent so that all existing booted() save hooks in AssessmentFinding
 * (Gap-to-Final cloning / rollback in AssessmentService) fire normally.
 *
 * Usage:
 *   php artisan import:risk-register \
 *       --project-id=1 \
 *       --file=storage/app/imports/risk_register.xlsx
 *
 * Options:
 *   --project-id   ID of the Project to attach assessments to.
 *                  If omitted, a sentinel project named
 *                  "Risk Register Import" is found-or-created.
 *   --file         Absolute or storage-relative path to the Excel file.
 *                  Defaults to storage/app/imports/risk_register.xlsx
 *   --fresh        Drop existing Risk Register findings for this project
 *                  before importing (useful for re-runs).
 *
 * Sheet layout:
 *   Each worksheet tab is treated as one department.  The command creates
 *   one ProjectAssessment (type=Gap) per sheet, named implicitly by the
 *   framework + project combination.  The assessment name is surfaced via
 *   the sheet name stored in overall_status for easy identification.
 *
 * Expected Excel columns (fuzzy-matched — exact names may vary):
 *   #, Asset/Process, Risk Owner, Date, Asset Value, Threat, Threat Level,
 *   Vulnerability, Impact C/I/A, Existing Control, Vuln Level, TV,
 *   Likelihood, Inherent Risk Rating, Risk Acceptance Status,
 *   Proposed Control, Communication, Impl From, Impl To,
 *   Implementation Status, Residual TV, Residual Likelihood,
 *   Residual Risk Rating, Follow-up Note
 *
 * Field mapping (see RiskRegisterFindingImport for full details):
 *   Inherent Risk Rating      -> risk_rating         (High/Medium/Low/None)
 *   Implementation Status     -> status              (Open/In Progress/Closed)
 *   Asset/Process+Threat+Vuln -> observation         (concatenated)
 *   Existing+Proposed Control -> recommendation      (concatenated)
 *   Impact C/I/A              -> impact
 *   Follow-up Note            -> gap_description
 *   Risk Acceptance Status    -> is_compliant        (true when "Accepted")
 *   (sentinel control)        -> framework_control_id
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
        //
        //    AssessmentFinding.framework_control_id is a NOT NULL FK.
        //    Risk Register rows are not tied to a specific control, so we
        //    use one sentinel control per framework as the FK target.
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
        // 5. Read sheet names from the workbook
        // ----------------------------------------------------------------
        try {
            $reader     = IOFactory::createReaderForFile($filePath);
            $sheetNames = $reader->listWorksheetNames($filePath);
        } catch (\Exception $e) {
            $this->error('Could not read sheet names: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Sheets found: ' . implode(', ', $sheetNames));

        // ----------------------------------------------------------------
        // 6. Process each sheet as one department / assessment
        // ----------------------------------------------------------------
        $totalImported = 0;

        foreach ($sheetNames as $sheetName) {
            $department = trim($sheetName);
            $this->line("\nProcessing sheet: {$department}");

            // Find or create a Gap assessment for this project + framework.
            // overall_status stores the department/sheet name so the record
            // is identifiable in the UI without a dedicated name column.
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
            $this->line("  Assessment id={$assessment->id} (" . ($assessment->wasRecentlyCreated ? 'created' : 'found') . ")");

            // Optionally wipe existing findings for a clean re-import
            if ($this->option('fresh')) {
                $deleted = $assessment->findings()->delete();
                $this->line("  --fresh: deleted {$deleted} existing findings.");
            }

            // Run the import for this sheet
            $importer = new RiskRegisterFindingImport($assessment->id, $sentinelControl->id);

            try {
                Excel::import($importer, $filePath, null, \Maatwebsite\Excel\Excel::XLSX, [
                    'sheet' => $sheetName,
                ]);
            } catch (\Exception $e) {
                $this->error("  Import failed for sheet '{$sheetName}': " . $e->getMessage());
                continue;
            }

            $this->info("  Imported {$importer->imported} findings.");
            $totalImported += $importer->imported;
        }

        $this->info("\nDone. Total findings imported: {$totalImported}");
        return self::SUCCESS;
    }
}
