<?php

namespace App\Modules\RiskManagement\Services;

use App\Modules\RiskManagement\Models\RiskRegister;
use App\Models\Asset;
use App\Models\Department;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\HeatmapConfig;
use App\Modules\RiskManagement\Models\RiskHeatmapSnapshot;
use App\Modules\RiskManagement\Support\Scoring\InherentRiskInput;
use App\Models\User;
use App\Models\Project;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WorkbookImportService
{
    private const DEFAULT_MAPPINGS = [
        '#' => 'serial_no',
        'Asset / Process / Service' => 'asset_process_service',
        'Risk Owner' => 'risk_owner',
        'Date' => 'risk_calculation_date',
        'Asset Value (BDT)' => 'asset_value_bdt',
        'Threat' => 'threats',
        'Threat Level (T)' => 'threat_level_t',
        'Vulnerability' => 'vulnerabilities',
        'Impact C' => 'impact_confidentiality',
        'Impact I' => 'impact_integrity',
        'Impact A' => 'impact_availability',
        'Existing Control' => 'existing_control',
        'Vuln. Level (AV)' => 'vulnerability_level_av',
        'TV (T+AV)' => 'tv_t_av',
        'Likelihood (LH)' => 'likelihood_lh',
        'Risk Rating (AV*TV*LH)' => 'risk_rating_avtvlh',
        'Measurement' => 'measurement',
        'Proposed Control' => 'proposed_control',
        'Communication' => 'communication',
        'Impl. From' => 'implementation_from',
        'Impl. To' => 'implementation_to',
        'Impl. Status' => 'implementation_status',
        'Residual TV' => 'residual_tv',
        'Residual LH' => 'residual_lh',
        'Residual Rating' => 'residual_rating',
        'Follow-up Note' => 'follow_up_note',
    ];

    /**
     * Get headers and suggest default mappings.
     */
    public function getHeaderMappings(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found at: {$filePath}");
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('Risk Register') ?? $spreadsheet->getActiveSheet();
        
        // Let's read row 3 which is usually the header row in our workbook
        $row3 = $sheet->rangeToArray('A3:Z3', null, true, true, true)[3] ?? [];
        $mappings = [];

        foreach ($row3 as $colLetter => $header) {
            $headerText = trim((string)$header);
            if (empty($headerText)) {
                continue;
            }

            // Find closest mapping suggestion
            $dbField = null;
            $bestScore = 0;
            
            foreach (self::DEFAULT_MAPPINGS as $excelHeader => $field) {
                if (strcasecmp($headerText, $excelHeader) === 0) {
                    $dbField = $field;
                    break;
                }
                
                similar_text(strtolower($headerText), strtolower($excelHeader), $percent);
                if ($percent > $bestScore && $percent > 60) {
                    $bestScore = $percent;
                    $dbField = $field;
                }
            }

            $mappings[] = [
                'col' => $colLetter,
                'header' => $headerText,
                'db_field' => $dbField,
            ];
        }

        return $mappings;
    }

    /**
     * Perform dry-run validation.
     */
    public function dryRun(string $filePath, array $columnMappings): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found at: {$filePath}");
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('Risk Register') ?? $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        // Header mapping lookup: db_field => col_letter
        $lookup = [];
        foreach ($columnMappings as $mapping) {
            if (!empty($mapping['db_field'])) {
                $lookup[$mapping['db_field']] = $mapping['col'];
            }
        }

        $results = [];
        // Process rows starting from row 4
        foreach ($rows as $index => $row) {
            if ($index < 4) {
                continue;
            }

            $serialNo = isset($lookup['serial_no']) ? trim((string)($row[$lookup['serial_no']] ?? '')) : '';
            $assetProcess = isset($lookup['asset_process_service']) ? trim((string)($row[$lookup['asset_process_service']] ?? '')) : '';

            // Skip empty rows
            if (empty($serialNo) && empty($assetProcess)) {
                continue;
            }

            $rowErrors = [];
            $rowWarnings = [];

            // 1. Validate Serial No
            if (empty($serialNo)) {
                $rowErrors[] = "Serial number (#) is required.";
            }

            // 2. Validate Asset / Process / Service
            if (empty($assetProcess)) {
                $rowErrors[] = "Asset / Process / Service description is required.";
            }

            // 3. Validate Date
            $dateVal = isset($lookup['risk_calculation_date']) ? trim((string)($row[$lookup['risk_calculation_date']] ?? '')) : '';
            if (!empty($dateVal)) {
                try {
                    Carbon::parse($dateVal);
                } catch (\Exception $e) {
                    $rowWarnings[] = "Invalid calculation date format ('{$dateVal}'), using current date.";
                }
            }

            // 4. Validate Asset Value BDT
            $valBdt = isset($lookup['asset_value_bdt']) ? $row[$lookup['asset_value_bdt']] : 0;
            if (!is_numeric(str_replace(',', '', (string)$valBdt))) {
                $rowWarnings[] = "Asset value ('{$valBdt}') is not numeric, defaulting to 0.";
            }

            // 5. Validate integer ranges (1-5)
            $numericFields = [
                'threat_level_t' => 'Threat Level (T)',
                'vulnerability_level_av' => 'Vulnerability Level (AV)',
                'likelihood_lh' => 'Likelihood (LH)',
                'impact_confidentiality' => 'Confidentiality Impact',
                'impact_integrity' => 'Integrity Impact',
                'impact_availability' => 'Availability Impact',
                'residual_tv' => 'Residual TV',
                'residual_lh' => 'Residual LH',
            ];

            foreach ($numericFields as $field => $label) {
                $val = isset($lookup[$field]) ? intval($row[$lookup[$field]] ?? 0) : 0;
                if ($val < 1 || $val > 5) {
                    $rowWarnings[] = "{$label} value ({$val}) is outside normal 1-5 range.";
                }
            }

            // Extract values for scoring preview
            $threatLevel = isset($lookup['threat_level_t']) ? intval($row[$lookup['threat_level_t']] ?? 1) : 1;
            $vulnLevel = isset($lookup['vulnerability_level_av']) ? intval($row[$lookup['vulnerability_level_av']] ?? 1) : 1;
            $likelihood = isset($lookup['likelihood_lh']) ? intval($row[$lookup['likelihood_lh']] ?? 1) : 1;
            $residualTv = isset($lookup['residual_tv']) ? intval($row[$lookup['residual_tv']] ?? 1) : 1;
            $residualLh = isset($lookup['residual_lh']) ? intval($row[$lookup['residual_lh']] ?? 1) : 1;

            // Workbook exact values
            $workbookTv = isset($lookup['tv_t_av']) ? intval($row[$lookup['tv_t_av']] ?? 0) : ($threatLevel + $vulnLevel);
            $workbookRating = isset($lookup['risk_rating_avtvlh']) ? intval($row[$lookup['risk_rating_avtvlh']] ?? 0) : ($vulnLevel * $workbookTv * $likelihood);
            $workbookResidual = isset($lookup['residual_rating']) ? intval($row[$lookup['residual_rating']] ?? 0) : ($residualTv * $residualLh);

            // Computed values
            $computedTv = $threatLevel + $vulnLevel;
            $computedRating = $vulnLevel * $computedTv * $likelihood;
            $computedResidual = $residualTv * $residualLh;

            if ($workbookTv !== $computedTv) {
                $rowWarnings[] = "Reconciliation: TV (T+AV) imported score ({$workbookTv}) differs from calculated formula ({$computedTv}).";
            }
            if ($workbookRating !== $computedRating) {
                $rowWarnings[] = "Reconciliation: Inherent Risk Rating imported score ({$workbookRating}) differs from calculated formula ({$computedRating}).";
            }
            if ($workbookResidual !== $computedResidual) {
                $rowWarnings[] = "Reconciliation: Residual Rating imported score ({$workbookResidual}) differs from calculated formula ({$computedResidual}).";
            }

            // Determine status color levels
            $inherentLevel = RiskRegister::scoreToLevel($workbookRating);
            $residualLevel = RiskRegister::scoreToLevel($workbookResidual);

            $results[] = [
                'row_index' => $index,
                'serial_no' => $serialNo,
                'asset_process' => $assetProcess,
                'risk_owner' => isset($lookup['risk_owner']) ? trim((string)($row[$lookup['risk_owner']] ?? '')) : '',
                'inherent_rating' => $workbookRating,
                'inherent_level' => $inherentLevel,
                'residual_rating' => $workbookResidual,
                'residual_level' => $residualLevel,
                'status' => count($rowErrors) > 0 ? 'Failed' : (count($rowWarnings) > 0 ? 'Warning' : 'Passed'),
                'errors' => $rowErrors,
                'warnings' => $rowWarnings,
                // Raw cell values to display formatted row preview identical to workbook
                'raw_values' => array_map(fn($col) => $row[$col] ?? '', $lookup),
            ];
        }

        return $results;
    }

    /**
     * Perform the actual import.
     */
    public function import(string $filePath, array $columnMappings, int $projectId): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found at: {$filePath}");
        }

        $adminUser = User::first() ?? User::create([
            'name' => 'System Admin',
            'email' => 'admin@compliancehub.com',
            'password' => bcrypt('password'),
        ]);

        $project = Project::findOrFail($projectId);

        // Pre-create/link HeatmapConfig
        HeatmapConfig::firstOrCreate(
            ['id' => 1],
            [
                'critical_threshold' => 128,
                'high_threshold' => 84,
                'medium_threshold' => 54,
                'low_threshold' => 53,
            ]
        );

        $spreadsheet = IOFactory::load($filePath);

        // 1. Process Framework & Control Mapping sheet
        $this->processControlMapping($spreadsheet);

        // 2. Process Risk Register sheet
        $sheet = $spreadsheet->getSheetByName('Risk Register') ?? $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        // Header mapping lookup: db_field => col_letter
        $lookup = [];
        foreach ($columnMappings as $mapping) {
            if (!empty($mapping['db_field'])) {
                $lookup[$mapping['db_field']] = $mapping['col'];
            }
        }

        $importedCount = 0;
        $scoringService = new RiskScoringService();

        DB::transaction(function () use ($rows, $lookup, $project, $adminUser, $scoringService, &$importedCount) {
            foreach ($rows as $index => $row) {
                if ($index < 4) {
                    continue;
                }

                $serialNo = isset($lookup['serial_no']) ? trim((string)($row[$lookup['serial_no']] ?? '')) : '';
                $assetProcess = isset($lookup['asset_process_service']) ? trim((string)($row[$lookup['asset_process_service']] ?? '')) : '';

                if (empty($serialNo) || empty($assetProcess)) {
                    continue;
                }

                $riskOwner = isset($lookup['risk_owner']) ? trim((string)($row[$lookup['risk_owner']] ?? '')) : 'Unknown';

                // Resolve calculation date
                $calculationDate = now();
                $dateVal = isset($lookup['risk_calculation_date']) ? trim((string)($row[$lookup['risk_calculation_date']] ?? '')) : '';
                if (!empty($dateVal)) {
                    try {
                        $calculationDate = Carbon::parse($dateVal);
                    } catch (\Exception $e) {
                        $calculationDate = now();
                    }
                }

                // Resolve BDT asset value
                $valBdtRaw = isset($lookup['asset_value_bdt']) ? $row[$lookup['asset_value_bdt']] : 0;
                $assetValueBdt = floatval(str_replace(',', '', (string)$valBdtRaw));

                $threatStr = isset($lookup['threats']) ? trim((string)($row[$lookup['threats']] ?? 'General Threat')) : 'General Threat';
                $threatLevel = isset($lookup['threat_level_t']) ? intval($row[$lookup['threat_level_t']] ?? 1) : 1;
                $vulnStr = isset($lookup['vulnerabilities']) ? trim((string)($row[$lookup['vulnerabilities']] ?? 'General Vulnerability')) : 'General Vulnerability';
                
                $impactC = isset($lookup['impact_confidentiality']) ? intval($row[$lookup['impact_confidentiality']] ?? 1) : 1;
                $impactI = isset($lookup['impact_integrity']) ? intval($row[$lookup['impact_integrity']] ?? 1) : 1;
                $impactA = isset($lookup['impact_availability']) ? intval($row[$lookup['impact_availability']] ?? 1) : 1;
                
                $existingControl = isset($lookup['existing_control']) ? trim((string)($row[$lookup['existing_control']] ?? 'None')) : 'None';
                $vulnLevelAv = isset($lookup['vulnerability_level_av']) ? intval($row[$lookup['vulnerability_level_av']] ?? 1) : 1;
                
                // Store workbook exact scores
                $tvTAv = isset($lookup['tv_t_av']) ? intval($row[$lookup['tv_t_av']] ?? 0) : ($threatLevel + $vulnLevelAv);
                $likelihoodLh = isset($lookup['likelihood_lh']) ? intval($row[$lookup['likelihood_lh']] ?? 1) : 1;
                $riskRating = isset($lookup['risk_rating_avtvlh']) ? intval($row[$lookup['risk_rating_avtvlh']] ?? 0) : ($vulnLevelAv * $tvTAv * $likelihoodLh);

                $measurementRaw = isset($lookup['measurement']) ? trim((string)($row[$lookup['measurement']] ?? 'Not Accepted')) : 'Not Accepted';
                $measurement = (strcasecmp($measurementRaw, 'Accepted') === 0) ? 'Accepted' : 'Not Accepted';

                $proposedControl = isset($lookup['proposed_control']) ? trim((string)($row[$lookup['proposed_control']] ?? '')) : null;
                $communication = isset($lookup['communication']) ? trim((string)($row[$lookup['communication']] ?? '')) : null;

                // Implementation dates
                $implFrom = null;
                $fromVal = isset($lookup['implementation_from']) ? trim((string)($row[$lookup['implementation_from']] ?? '')) : '';
                if (!empty($fromVal)) {
                    try {
                        $implFrom = Carbon::parse($fromVal)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $implFrom = null;
                    }
                }
                $implTo = null;
                $toVal = isset($lookup['implementation_to']) ? trim((string)($row[$lookup['implementation_to']] ?? '')) : '';
                if (!empty($toVal)) {
                    try {
                        $implTo = Carbon::parse($toVal)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $implTo = null;
                    }
                }

                // Implementation status
                $statusRaw = isset($lookup['implementation_status']) ? trim((string)($row[$lookup['implementation_status']] ?? 'Not Started')) : 'Not Started';
                $status = 'Not Started';
                if (strcasecmp($statusRaw, 'Pending') === 0) {
                    $status = 'Pending';
                } elseif (strcasecmp($statusRaw, 'In Progress') === 0 || strcasecmp($statusRaw, 'In-Progress') === 0) {
                    $status = 'In Progress';
                } elseif (strcasecmp($statusRaw, 'Completed') === 0) {
                    $status = 'Completed';
                }

                $residualTv = isset($lookup['residual_tv']) ? intval($row[$lookup['residual_tv']] ?? 1) : 1;
                $residualLh = isset($lookup['residual_lh']) ? intval($row[$lookup['residual_lh']] ?? 1) : 1;
                $residualRating = isset($lookup['residual_rating']) ? intval($row[$lookup['residual_rating']] ?? 0) : ($residualTv * $residualLh);

                $followUpNote = isset($lookup['follow_up_note']) ? trim((string)($row[$lookup['follow_up_note']] ?? '')) : null;

                // Create or link Department
                Department::firstOrCreate(['name' => $riskOwner]);

                // Create or link Asset
                $asset = Asset::firstOrCreate(
                    ['name' => $assetProcess],
                    [
                        'type' => 'Process',
                        'value_bdt' => $assetValueBdt,
                        'owner_id' => $adminUser->id,
                    ]
                );

                // Compute canonical calculations
                $computedTv = $threatLevel + $vulnLevelAv;
                $computedRating = $vulnLevelAv * $computedTv * $likelihoodLh;
                $computedResidual = $residualTv * $likelihoodLh; // Using likelihood_lh as standard residual approximation if residual_lh is not set, else use residual_lh
                $computedResidual = $residualTv * $residualLh;

                // Try to find a matching framework control to auto-link
                $frameworkControlId = null;
                $matchedControl = $this->findMatchingControl($threatStr, $existingControl, $proposedControl);
                if ($matchedControl) {
                    $frameworkControlId = $matchedControl->id;
                }

                // Create the risk register entry
                $riskEntry = RiskRegister::updateOrCreate(
                    ['serial_no' => $serialNo, 'project_id' => $project->id],
                    [
                        'framework_control_id' => $frameworkControlId,
                        'asset_process_service' => $assetProcess,
                        'risk_owner' => $riskOwner,
                        'risk_calculation_date' => $calculationDate->format('Y-m-d'),
                        'asset_value_bdt' => $assetValueBdt,
                        'threats' => [$threatStr],
                        'threat_level_t' => $threatLevel,
                        'vulnerabilities' => [$vulnStr],
                        'impact_confidentiality' => $impactC,
                        'impact_integrity' => $impactI,
                        'impact_availability' => $impactA,
                        'existing_control' => $existingControl,
                        'vulnerability_level_av' => $vulnLevelAv,
                        'tv_t_av' => $tvTAv, // raw workbook value
                        'likelihood_lh' => $likelihoodLh,
                        'risk_rating_avtvlh' => $riskRating, // raw workbook value
                        'measurement' => $measurement,
                        'proposed_control' => $proposedControl,
                        'communication' => $communication,
                        'implementation_from' => $implFrom,
                        'implementation_to' => $implTo,
                        'implementation_status' => $status,
                        'residual_tv' => $residualTv,
                        'residual_lh' => $residualLh, // raw workbook value
                        'residual_rating' => $residualRating, // raw workbook value
                        'follow_up_note' => $followUpNote,
                        'category' => 'Cybersecurity',
                        'department' => $riskOwner,
                        'owner_user_id' => $adminUser->id,
                        'asset_id' => $asset->id,
                        'evidence_ids' => [],
                        'source' => 'import',
                        'legacy_source_id' => 'workbook_row_' . $index,
                        'created_by' => $adminUser->id,
                        'updated_by' => $adminUser->id,
                        
                        // Populate computed columns
                        'computed_tv' => $computedTv,
                        'computed_risk_rating' => $computedRating,
                        'computed_residual_rating' => $computedResidual,
                        
                        'custom_fields' => [
                            'raw_imported_tv' => $tvTAv,
                            'original_row_number' => $index,
                        ]
                    ]
                );

                // Record a dedicated inherent (before-controls) score for this
                // imported row, preserving the formula version + input snapshot.
                $scoringService->scoreAndRecord(
                    new InherentRiskInput(
                        threatLevel: $threatLevel,
                        vulnerabilityLevel: $vulnLevelAv,
                        impactDimensions: [
                            'confidentiality' => $impactC,
                            'integrity'       => $impactI,
                            'availability'    => $impactA,
                        ],
                        likelihood: $likelihoodLh,
                        assetValue: $assetValueBdt,
                        category: 'Cybersecurity',
                        riskRegisterId: $riskEntry->id
                    ),
                    recordedBy: $adminUser->id,
                    source: 'import'
                );

                $importedCount++;
            }
        });

        // 3. Populate heatmap snapshot and dashboard seed aggregates
        $this->seedDashboardAndHeatmap($project->id);

        return [
            'success' => true,
            'imported_count' => $importedCount,
        ];
    }

    /**
     * Parse and seed frameworks and framework controls from Control Mapping sheet.
     */
    private function processControlMapping($spreadsheet): void
    {
        $sheet = $spreadsheet->getSheetByName('Control Mapping');
        if (!$sheet) {
            return;
        }

        $rows = $sheet->toArray(null, true, true, true);
        if (count($rows) < 3) {
            return;
        }

        // Framework mappings based on row 2 headers
        $frameworks = [
            'pci_dss' => ['name' => 'PCI DSS', 'version' => '4.0', 'ref_col' => 'A', 'desc_col' => 'B'],
            'iso_27001' => ['name' => 'ISO 27001', 'version' => '2022', 'ref_col' => 'D', 'desc_col' => 'E'],
            'bb_ict' => ['name' => 'BB ICT Guidelines', 'version' => 'v1', 'ref_col' => 'G', 'desc_col' => 'H'],
            'swift_cscf' => ['name' => 'SWIFT CSCF', 'version' => '2026', 'ref_col' => 'J', 'desc_col' => 'K'],
        ];

        $frameworkModels = [];
        foreach ($frameworks as $slug => $info) {
            $frameworkModels[$slug] = Framework::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $info['name'],
                    'version' => $info['version'],
                    'description' => "Unified control library for {$info['name']}",
                    'is_active' => true,
                ]
            );
        }

        foreach ($rows as $index => $row) {
            // Header rows are 1 and 2
            if ($index < 3) {
                continue;
            }

            // Collect all framework refs for this row (for cross-referencing)
            $rowRefs = [];
            foreach ($frameworks as $slug => $info) {
                $ref = isset($row[$info['ref_col']]) ? trim((string)$row[$info['ref_col']]) : '';
                $desc = isset($row[$info['desc_col']]) ? trim((string)$row[$info['desc_col']]) : '';
                $rowRefs[$slug] = ['ref' => $ref, 'desc' => $desc];
            }

            foreach ($frameworks as $slug => $info) {
                $refData = $rowRefs[$slug];
                $ref = $refData['ref'];
                $desc = $refData['desc'];

                if (empty($ref) || strcasecmp($ref, 'no relevant control match found') === 0) {
                    continue;
                }

                // Build cross-references from the other framework columns in this row
                $pciDssRef = ($slug !== 'pci_dss')     ? ($rowRefs['pci_dss']['ref'] ?? null)     : null;
                $isoRef    = ($slug !== 'iso_27001')   ? ($rowRefs['iso_27001']['ref'] ?? null)   : null;
                $bbIctRef  = ($slug !== 'bb_ict')       ? ($rowRefs['bb_ict']['ref'] ?? null)     : null;
                $swiftRef  = ($slug !== 'swift_cscf')   ? ($rowRefs['swift_cscf']['ref'] ?? null) : null;

                // If ISO has comma separated control IDs (like "5.1, 5.37"), we can split and insert
                $refs = ($slug === 'iso_27001') ? explode(',', $ref) : [$ref];

                foreach ($refs as $singleRef) {
                    $singleRef = trim($singleRef);
                    if (empty($singleRef)) {
                        continue;
                    }

                    FrameworkControl::updateOrCreate(
                        [
                            'framework_id' => $frameworkModels[$slug]->id,
                            'control_id' => $singleRef,
                        ],
                        [
                            'domain'                => $this->deriveDomain($singleRef, $slug),
                            'requirement_description' => $desc ?: "Description for control {$singleRef}",
                            'required_evidence'     => 'Audit reports, Policy documents',
                            'pci_dss_ref'           => $pciDssRef,
                            'iso_ref'               => $isoRef,
                            'bb_ict_ref'            => $bbIctRef,
                            'swift_ref'             => $swiftRef,
                            'status'                => 'active',
                        ]
                    );
                }
            }
        }
    }

    /**
     * Derive a domain grouping for a control reference.
     */
    private function deriveDomain(string $ref, string $frameworkSlug): string
    {
        if ($frameworkSlug === 'pci_dss') {
            $parts = explode('.', $ref);
            return 'Requirement ' . ($parts[0] ?? '1');
        }
        if ($frameworkSlug === 'iso_27001') {
            return 'ISO Section ' . explode('.', $ref)[0];
        }
        return 'General Control Security';
    }

    /**
     * Attempt to find a matching framework control by text scan.
     */
    private function findMatchingControl(?string $threat, ?string $existingControl, ?string $proposedControl): ?FrameworkControl
    {
        $text = strtolower($threat . ' ' . $existingControl . ' ' . $proposedControl);
        
        // Scan active framework controls and check if any control_id or keyword is present
        // Let's do a simple cache query
        $controls = FrameworkControl::take(200)->get();
        foreach ($controls as $control) {
            $controlId = strtolower($control->control_id);
            if (strpos($text, $controlId) !== false) {
                return $control;
            }
        }

        return null;
    }

    /**
     * Seed/recalculate aggregates for Project Dashboard and Heatmap Snapshots.
     */
    public function seedDashboardAndHeatmap(int $projectId): void
    {
        // 1. Snapshot Heatmap Matrix
        $calc = new RiskCalculationService();
        $service = new RiskRegisterService($calc);
        
        // Save inherent and residual snapshots
        $service->snapshotHeatmap($projectId, 'inherent');
        $service->snapshotHeatmap($projectId, 'residual');
    }
}
