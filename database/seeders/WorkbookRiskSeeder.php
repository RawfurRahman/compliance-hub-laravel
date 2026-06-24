<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Models\Asset;
use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use App\Models\HeatmapConfig;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class WorkbookRiskSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = base_path('storage/app/imports/risk_register.xlsx');

        if (!file_exists($filePath)) {
            throw new \Exception("Risk register workbook not found at: {$filePath}");
        }

        // 1. Resolve default users
        $adminUser = User::first() ?? User::create([
            'name' => 'System Admin',
            'email' => 'admin@compliancehub.com',
            'password' => bcrypt('password'),
        ]);

        // 2. Resolve default project
        $project = Project::first() ?? Project::create([
            'name' => 'Cybersecurity Compliance Hub',
            'user_id' => $adminUser->id,
            'status' => 'Active'
        ]);

        // 3. Seed Heatmap config defaults
        HeatmapConfig::firstOrCreate(
            ['id' => 1],
            [
                'critical_threshold' => 128,
                'high_threshold' => 84,
                'medium_threshold' => 54,
                'low_threshold' => 53,
            ]
        );

        // 4. Load Excel Workbook
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('Risk Register');

        if (!$sheet) {
            throw new \Exception("Sheet 'Risk Register' not found in the workbook.");
        }

        $rows = $sheet->toArray(null, true, true, true);

        // Start processing from Row 4 (Row 3 is header)
        $totalImported = 0;
        foreach ($rows as $index => $row) {
            if ($index < 4) {
                continue;
            }

            // A row is valid if it has a serial number and an asset/process
            $serialNo = isset($row['A']) ? trim((string)$row['A']) : '';
            $assetProcess = isset($row['B']) ? trim((string)$row['B']) : '';

            if (empty($serialNo) || empty($assetProcess)) {
                continue;
            }

            // Raw values parsing
            $riskOwner = isset($row['C']) ? trim((string)$row['C']) : 'Unknown';
            
            // Format calculations date
            $calculationDate = now();
            if (!empty($row['D'])) {
                try {
                    $calculationDate = Carbon::parse($row['D']);
                } catch (\Exception $e) {
                    $calculationDate = now();
                }
            }

            $assetValueBdt = isset($row['E']) ? floatval(str_replace(',', '', (string)$row['E'])) : 0.00;
            $threatStr = isset($row['F']) ? trim((string)$row['F']) : 'General Threat';
            $threatLevel = isset($row['G']) ? intval($row['G']) : 1;
            $vulnStr = isset($row['H']) ? trim((string)$row['H']) : 'General Vulnerability';
            
            $impactC = isset($row['I']) ? intval($row['I']) : 1;
            $impactI = isset($row['J']) ? intval($row['J']) : 1;
            $impactA = isset($row['K']) ? intval($row['K']) : 1;
            
            $existingControl = isset($row['L']) ? trim((string)$row['L']) : 'None';
            $vulnLevelAv = isset($row['M']) ? intval($row['M']) : 1;
            
            // tv_t_av: store raw imported value
            $tvTAv = isset($row['N']) ? intval($row['N']) : ($threatLevel + $vulnLevelAv);
            
            $likelihoodLh = isset($row['O']) ? intval($row['O']) : 1;
            
            // Risk rating from workbook
            $riskRating = isset($row['P']) ? intval($row['P']) : ($vulnLevelAv * $tvTAv * $likelihoodLh);

            $measurementRaw = isset($row['Q']) ? trim((string)$row['Q']) : 'Not Accepted';
            $measurement = (strcasecmp($measurementRaw, 'Accepted') === 0) ? 'Accepted' : 'Not Accepted';

            $proposedControl = isset($row['R']) ? trim((string)$row['R']) : null;
            $communication = isset($row['S']) ? trim((string)$row['S']) : null;

            // Implementation dates
            $implFrom = null;
            if (!empty($row['T'])) {
                try {
                    $implFrom = Carbon::parse($row['T'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $implFrom = null;
                }
            }
            $implTo = null;
            if (!empty($row['U'])) {
                try {
                    $implTo = Carbon::parse($row['U'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $implTo = null;
                }
            }

            // Implementation status
            $statusRaw = isset($row['V']) ? trim((string)$row['V']) : 'Not Started';
            $status = 'Not Started';
            if (strcasecmp($statusRaw, 'Pending') === 0) {
                $status = 'Pending';
            } elseif (strcasecmp($statusRaw, 'In Progress') === 0 || strcasecmp($statusRaw, 'In-Progress') === 0) {
                $status = 'In Progress';
            } elseif (strcasecmp($statusRaw, 'Completed') === 0) {
                $status = 'Completed';
            }

            $residualTv = isset($row['W']) ? intval($row['W']) : 1;
            $residualLh = isset($row['X']) ? intval($row['X']) : 1;
            $residualRating = isset($row['Y']) ? intval($row['Y']) : ($residualTv * $residualLh);

            $followUpNote = isset($row['Z']) ? trim((string)$row['Z']) : null;

            // Seeding supporting entities: Department
            Department::firstOrCreate(['name' => $riskOwner]);

            // Seeding supporting entities: Asset
            $asset = Asset::firstOrCreate(
                ['name' => $assetProcess],
                [
                    'type' => 'Process',
                    'value_bdt' => $assetValueBdt,
                    'owner_id' => $adminUser->id,
                ]
            );

            // Populate threats and vulnerabilities as JSON arrays
            $threatsArray = [$threatStr];
            $vulnsArray = [$vulnStr];

            // Replicate Risk Register Entry
            RiskRegister::updateOrCreate(
                ['serial_no' => $serialNo],
                [
                    'project_id' => $project->id,
                    'framework_control_id' => null,
                    'asset_process_service' => $assetProcess,
                    'risk_owner' => $riskOwner,
                    'risk_calculation_date' => $calculationDate->format('Y-m-d'),
                    'asset_value_bdt' => $assetValueBdt,
                    'threats' => $threatsArray,
                    'threat_level_t' => $threatLevel,
                    'vulnerabilities' => $vulnsArray,
                    'impact_confidentiality' => $impactC,
                    'impact_integrity' => $impactI,
                    'impact_availability' => $impactA,
                    'existing_control' => $existingControl,
                    'vulnerability_level_av' => $vulnLevelAv,
                    'tv_t_av' => $tvTAv, // store exact workbook value
                    'likelihood_lh' => $likelihoodLh,
                    'risk_rating_avtvlh' => $riskRating, // store exact workbook value
                    'measurement' => $measurement,
                    'proposed_control' => $proposedControl,
                    'communication' => $communication,
                    'implementation_from' => $implFrom,
                    'implementation_to' => $implTo,
                    'implementation_status' => $status,
                    'residual_tv' => $residualTv,
                    'residual_lh' => $residualLh, // store exact workbook value
                    'residual_rating' => $residualRating, // store exact workbook value
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
                    'custom_fields' => [
                        'raw_imported_tv' => $tvTAv,
                        'original_row_number' => $index
                    ]
                ]
            );

            $totalImported++;
        }

        echo "WorkbookRiskSeeder completed. Total imported/seeded: {$totalImported} rows.\n";
    }
}
