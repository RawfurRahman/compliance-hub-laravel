<?php

namespace App\Modules\RiskManagement\Services;

use App\Modules\RiskManagement\Models\RiskRegister;
use App\Models\IsoGapAssessment;
use App\Models\PciGapAssessment;
use App\Models\User;
use App\Models\Asset;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class MigrationService
{
    /**
     * Map and migrate ISO and PCI legacy assessments to unified risks.
     */
    public function migrateLegacyAssessments(int $projectId): array
    {
        $adminUser = User::first() ?? User::create([
            'name' => 'System Admin',
            'email' => 'admin@compliancehub.com',
            'password' => bcrypt('password'),
        ]);

        $isoCount = 0;
        $pciCount = 0;

        DB::transaction(function () use ($projectId, $adminUser, &$isoCount, &$pciCount) {
            // 1. Migrate ISO Gap Assessments
            $isoItems = IsoGapAssessment::where('project_id', $projectId)->get();
            foreach ($isoItems as $iso) {
                $sourceId = 'iso_gap_assessment_' . $iso->id;

                // Check if already migrated
                if (RiskRegister::where('legacy_source_id', $sourceId)->exists()) {
                    continue;
                }

                // Map ratings
                $ratingConfig = $this->getIsoRatingConfig($iso->risk_rating);
                
                // Map status
                $status = 'Not Started';
                if ($iso->status === 'Closed') {
                    $status = 'Completed';
                } elseif ($iso->status === 'In Progress') {
                    $status = 'In Progress';
                }

                $riskOwner = 'IT Security Department';
                Department::firstOrCreate(['name' => $riskOwner]);
                $asset = Asset::firstOrCreate(
                    ['name' => $iso->observation_title ?: 'ISO Asset'],
                    [
                        'type' => 'Process',
                        'value_bdt' => 0.00,
                        'owner_id' => $adminUser->id,
                    ]
                );

                RiskRegister::create([
                    'project_id' => $projectId,
                    'serial_no' => 'ISO-' . $iso->serial_no . '-' . $iso->id,
                    'asset_process_service' => $iso->observation_title ?: 'ISO Observation',
                    'risk_owner' => $riskOwner,
                    'risk_calculation_date' => $iso->created_at ? $iso->created_at->format('Y-m-d') : now()->format('Y-m-d'),
                    'asset_value_bdt' => 0.00,
                    'threats' => [$iso->impact_risk ?: 'ISO Security Threat'],
                    'threat_level_t' => $ratingConfig['threat_level'],
                    'vulnerabilities' => [$iso->gap_description ?: 'ISO Control Weakness'],
                    'impact_confidentiality' => $ratingConfig['impact'],
                    'impact_integrity' => $ratingConfig['impact'],
                    'impact_availability' => $ratingConfig['impact'],
                    'existing_control' => $iso->current_state ?: 'None',
                    'vulnerability_level_av' => $ratingConfig['vuln_level'],
                    'tv_t_av' => $ratingConfig['threat_level'] + $ratingConfig['vuln_level'],
                    'likelihood_lh' => $ratingConfig['likelihood'],
                    'risk_rating_avtvlh' => $ratingConfig['risk_rating'],
                    'measurement' => $iso->risk_rating === 'High' ? 'Not Accepted' : 'Accepted',
                    'proposed_control' => $iso->recommendation,
                    'communication' => 'Logged during legacy ISO assessment',
                    'implementation_status' => $status,
                    'residual_tv' => $ratingConfig['residual_tv'],
                    'residual_lh' => $ratingConfig['residual_lh'],
                    'residual_rating' => $ratingConfig['residual_rating'],
                    'category' => 'Cybersecurity',
                    'department' => $riskOwner,
                    'owner_user_id' => $adminUser->id,
                    'asset_id' => $asset->id,
                    'evidence_ids' => [],
                    'source' => 'import',
                    'legacy_source_id' => $sourceId,
                    'created_by' => $adminUser->id,
                    'updated_by' => $adminUser->id,
                    'computed_tv' => $ratingConfig['threat_level'] + $ratingConfig['vuln_level'],
                    'computed_risk_rating' => $ratingConfig['risk_rating'],
                    'computed_residual_rating' => $ratingConfig['residual_rating'],
                ]);

                $isoCount++;
            }

            // 2. Migrate PCI Gap Assessments
            $pciItems = PciGapAssessment::where('project_id', $projectId)->get();
            foreach ($pciItems as $pci) {
                $sourceId = 'pci_gap_assessment_' . $pci->id;

                // Check if already migrated
                if (RiskRegister::where('legacy_source_id', $sourceId)->exists()) {
                    continue;
                }

                // Map status
                $status = 'Not Started';
                if ($pci->status === 'Compliant' || $pci->status === 'In Place' || $pci->status === 'Yes') {
                    $status = 'Completed';
                }

                $riskOwner = 'PCI Compliance Team';
                Department::firstOrCreate(['name' => $riskOwner]);
                $asset = Asset::firstOrCreate(
                    ['name' => 'PCI Requirement ' . $pci->id],
                    [
                        'type' => 'Compliance',
                        'value_bdt' => 0.00,
                        'owner_id' => $adminUser->id,
                    ]
                );

                // Default medium rating config
                $ratingConfig = $this->getIsoRatingConfig('Medium');

                RiskRegister::create([
                    'project_id' => $projectId,
                    'serial_no' => 'PCI-' . $pci->id,
                    'asset_process_service' => $pci->requirement_text ?: 'PCI Requirement',
                    'risk_owner' => $riskOwner,
                    'risk_calculation_date' => $pci->created_at ? $pci->created_at->format('Y-m-d') : now()->format('Y-m-d'),
                    'asset_value_bdt' => 0.00,
                    'threats' => ['Non-compliance with PCI DSS Requirement'],
                    'threat_level_t' => $ratingConfig['threat_level'],
                    'vulnerabilities' => ['Unimplemented PCI controls'],
                    'impact_confidentiality' => $ratingConfig['impact'],
                    'impact_integrity' => $ratingConfig['impact'],
                    'impact_availability' => $ratingConfig['impact'],
                    'existing_control' => $pci->comments ?: 'None',
                    'vulnerability_level_av' => $ratingConfig['vuln_level'],
                    'tv_t_av' => $ratingConfig['threat_level'] + $ratingConfig['vuln_level'],
                    'likelihood_lh' => $ratingConfig['likelihood'],
                    'risk_rating_avtvlh' => $ratingConfig['risk_rating'],
                    'measurement' => 'Not Accepted',
                    'proposed_control' => $pci->requirement_text,
                    'communication' => 'Logged during legacy PCI assessment',
                    'implementation_status' => $status,
                    'residual_tv' => $ratingConfig['residual_tv'],
                    'residual_lh' => $ratingConfig['residual_lh'],
                    'residual_rating' => $ratingConfig['residual_rating'],
                    'category' => 'Compliance',
                    'department' => $riskOwner,
                    'owner_user_id' => $adminUser->id,
                    'asset_id' => $asset->id,
                    'evidence_ids' => [],
                    'source' => 'import',
                    'legacy_source_id' => $sourceId,
                    'created_by' => $adminUser->id,
                    'updated_by' => $adminUser->id,
                    'computed_tv' => $ratingConfig['threat_level'] + $ratingConfig['vuln_level'],
                    'computed_risk_rating' => $ratingConfig['risk_rating'],
                    'computed_residual_rating' => $ratingConfig['residual_rating'],
                ]);

                $pciCount++;
            }
        });

        // Trigger updates of dashboard metric snapshots
        $calc = new RiskCalculationService();
        $importService = new WorkbookImportService();
        $importService->seedDashboardAndHeatmap($projectId);

        return [
            'success' => true,
            'iso_migrated' => $isoCount,
            'pci_migrated' => $pciCount,
        ];
    }

    private function getIsoRatingConfig(string $rating): array
    {
        $rating = ucfirst(strtolower(trim($rating)));
        $config = config("rmm.legacy_mapping.iso.rating_config.$rating");

        if ($config) {
            return $config;
        }

        // Low / Default
        return config('rmm.legacy_mapping.iso.rating_config.Low', [
            'threat_level'    => 2,
            'vuln_level'      => 2,
            'likelihood'      => 2,
            'impact'          => 2,
            'risk_rating'     => 16,
            'residual_tv'     => 1,
            'residual_lh'     => 1,
            'residual_rating' => 1,
        ]);
    }
}
