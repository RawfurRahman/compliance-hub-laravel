<?php

namespace App\Services;

use App\Models\AssessmentFinding;
use App\Models\EvidenceFile;
use App\Models\ProjectAssessment;
use Illuminate\Support\Facades\Log;

class GapAssessmentReportService
{
    public function generateReportSection(EvidenceFile $evidence): array
    {
        $reportData = $evidence->analysis_report_data ?? [];
        $control = $evidence->frameworkControl;

        return [
            'control_ref' => $reportData['control_ref'] ?? ($control->control_id ?? ''),
            'requirement_description' => $reportData['requirement_description'] ?? ($control->requirement_description ?? ''),
            'current_implementation_status' => $reportData['current_implementation_status'] ?? '',
            'gap_description' => $reportData['gap_description'] ?? '',
            'impact_assessment' => $reportData['impact_assessment'] ?? '',
            'recommended_action' => $reportData['recommended_action'] ?? '',
            'risk_rating' => $reportData['risk_rating'] ?? 'None',
            'is_compliant' => $reportData['is_compliant'] ?? false,
            'observation' => $reportData['observation'] ?? $evidence->ai_observations ?? '',
            'required_evidence' => $reportData['required_evidence'] ?? ($control->required_evidence ?? ''),
            'compliance_status' => $reportData['compliance_status'] ?? 'Not Assessed',
            'auditor_notes' => $reportData['auditor_notes'] ?? null,
            'evidence_file_id' => $evidence->id,
            'original_filename' => $evidence->original_filename,
            'reviewed_by' => $evidence->approvedBy?->username,
            'reviewed_at' => $evidence->ai_analysis_approved_at?->toDateTimeString(),
            'gap_category' => $reportData['gap_category'] ?? '',
            'non_compliant_details' => $reportData['non_compliant_details'] ?? '',
            'compliant_description' => $reportData['compliant_description'] ?? '',
            'remediation_plan' => $reportData['remediation_plan'] ?? '',
            'evidence_provided' => $reportData['evidence_provided'] ?? '',
            'test_results' => $reportData['test_results'] ?? '',
            'meets_standard' => $reportData['meets_standard'] ?? false,
        ];
    }

    public function sendToGapAssessment(EvidenceFile $evidence): AssessmentFinding
    {
        $reportData = $evidence->analysis_report_data ?? [];

        if (! $evidence->framework_control_id) {
            throw new \RuntimeException('Evidence file has no framework_control_id');
        }

        $control = $evidence->frameworkControl;
        if (! $control) {
            throw new \RuntimeException("FrameworkControl {$evidence->framework_control_id} not found");
        }

        $assessment = ProjectAssessment::firstOrCreate(
            [
                'project_id' => $evidence->project_id,
                'framework_id' => $control->framework_id,
                'type' => 'Gap',
            ],
            [
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
            ]
        );

        $riskRating = $reportData['risk_rating'] ?? 'None';
        $isCompliant = $reportData['is_compliant'] ?? false;

        $finding = AssessmentFinding::updateOrCreate(
            [
                'project_assessment_id' => $assessment->id,
                'framework_control_id' => $evidence->framework_control_id,
            ],
            [
                // NOTE: 'status' in $reportData is reserved for the AI's three-class
                // compliance verdict (compliant/partial/non_compliant), consumed by the
                // evaluation harness (EvaluationRunService::resolveVerdict) — the
                // AssessmentFinding workflow status lives under 'workflow_status' instead
                // to avoid clobbering it.
                'status' => $reportData['workflow_status'] ?? ($isCompliant ? 'Closed' : 'Open'),
                'risk_rating' => $riskRating,
                'observation' => $reportData['observation'] ?? $evidence->ai_observations ?? '',
                'gap_description' => $reportData['gap_description'] ?? '',
                'impact' => $reportData['impact_assessment'] ?? '',
                'recommendation' => $reportData['recommended_action'] ?? '',
                'due_date' => $reportData['due_date'] ?? null,
                'is_compliant' => $isCompliant,
                'is_applicable' => true,
                'gap_category' => $reportData['gap_category'] ?? '',
                'non_compliant_details' => $reportData['non_compliant_details'] ?? '',
                'compliant_description' => $reportData['compliant_description'] ?? '',
                'remediation_plan' => $reportData['remediation_plan'] ?? '',
                'evidence_provided' => $reportData['evidence_provided'] ?? '',
                'test_results' => $reportData['test_results'] ?? '',
                'meets_standard' => $reportData['meets_standard'] ?? false,
                'auditor_notes' => $reportData['auditor_notes'] ?? '',
                'source_type' => 'evidence',
                'source_id' => $evidence->id,
            ]
        );

        $existingGaps = $finding->ai_gaps ?? [];
        if (! empty($reportData['gap_description'])) {
            $existingGaps[] = [
                'gap' => $reportData['gap_description'],
                'severity' => strtolower($riskRating),
                'source_evidence_id' => $evidence->id,
            ];
            $finding->update(['ai_gaps' => $existingGaps]);
        }

        Log::info("GapAssessmentReport: Sent evidence_file_id {$evidence->id} to gap assessment finding_id {$finding->id}");

        return $finding->fresh();
    }

    public function syncToFinalReport(EvidenceFile $evidence): void
    {
        $finding = AssessmentFinding::where('source_type', 'evidence')
            ->where('source_id', $evidence->id)
            ->where('framework_control_id', $evidence->framework_control_id)
            ->first();

        if (! $finding) {
            Log::info("GapAssessmentReport: No assessment finding found for evidence_file_id {$evidence->id} to sync to final report");

            return;
        }

        $finalFinding = $this->syncFindingToFinalReport($finding);

        if ($finalFinding) {
            Log::info("GapAssessmentReport: Synced evidence_file_id {$evidence->id} to final assessment_id {$finalFinding->project_assessment_id}");
        }
    }

    /**
     * Clone a Gap-type finding into the project's Final-type assessment, creating the
     * Final assessment if it doesn't exist yet. Reused by both the evidence tracker's
     * "pass to final report" action and the observation workflow's "send to final assessment".
     */
    public function syncFindingToFinalReport(AssessmentFinding $finding): ?AssessmentFinding
    {
        $assessment = $finding->projectAssessment;
        if (! $assessment || $assessment->type !== 'Gap') {
            return null;
        }

        $finalAssessment = ProjectAssessment::firstOrCreate(
            [
                'project_id' => $assessment->project_id,
                'framework_id' => $assessment->framework_id,
                'type' => 'Final',
            ],
            [
                'start_date' => $assessment->start_date ?? now(),
                'end_date' => $assessment->end_date ?? now(),
                'cloned_from_id' => $assessment->id,
            ]
        );

        return AssessmentFinding::updateOrCreate(
            [
                'project_assessment_id' => $finalAssessment->id,
                'framework_control_id' => $finding->framework_control_id,
            ],
            [
                'status' => $finding->status,
                'risk_rating' => $finding->risk_rating,
                'observation' => $finding->observation,
                'gap_description' => $finding->gap_description,
                'impact' => $finding->impact,
                'recommendation' => $finding->recommendation,
                'is_compliant' => $finding->is_compliant,
                'gap_category' => $finding->gap_category,
                'non_compliant_details' => $finding->non_compliant_details,
                'compliant_description' => $finding->compliant_description,
                'remediation_plan' => $finding->remediation_plan,
                'evidence_provided' => $finding->evidence_provided,
                'test_results' => $finding->test_results,
                'meets_standard' => $finding->meets_standard,
                'cloned_from_finding_id' => $finding->id,
                'source_type' => $finding->source_type,
                'source_id' => $finding->source_id,
            ]
        );
    }

    public function getGapAssessmentReportData(EvidenceFile $evidence): array
    {
        $reportData = $evidence->analysis_report_data ?? [];
        $control = $evidence->frameworkControl;
        $finding = AssessmentFinding::where('source_type', 'evidence')
            ->where('source_id', $evidence->id)
            ->where('framework_control_id', $evidence->framework_control_id)
            ->first();

        $isCompliant = $reportData['is_compliant'] ?? ($finding->is_compliant ?? false);

        $output = [
            'evidence_id' => $evidence->id,
            'evidence_filename' => $evidence->original_filename,
            'assessment_status' => $isCompliant ? 'compliant' : 'non-compliant',
            'risk_rating' => $reportData['risk_rating'] ?? ($finding->risk_rating ?? 'None'),
            'gap_category' => $reportData['gap_category'] ?? ($finding->gap_category ?? ''),
            'non_compliant_details' => $reportData['non_compliant_details'] ?? ($finding->non_compliant_details ?? ''),
            'compliant_description' => $reportData['compliant_description'] ?? ($finding->compliant_description ?? ''),
            'remediation_plan' => $reportData['remediation_plan'] ?? ($finding->remediation_plan ?? ''),
            'evidence_provided' => $reportData['evidence_provided'] ?? '',
            'test_results' => $reportData['test_results'] ?? '',
            'meets_standard' => $reportData['meets_standard'] ?? ($finding->meets_standard ?? false),
            'control_ref' => $reportData['control_ref'] ?? ($control->control_id ?? ''),
            'domain' => $control->domain ?? '',
            'requirement_description' => $reportData['requirement_description'] ?? ($control->requirement_description ?? ''),
            'gap_description' => $reportData['gap_description'] ?? ($finding->gap_description ?? ''),
            'impact_assessment' => $reportData['impact_assessment'] ?? ($finding->impact ?? ''),
            'recommended_action' => $reportData['recommended_action'] ?? ($finding->recommendation ?? ''),
            'framework' => $control?->framework->name ?? '',
            'meets_standard_bool' => $isCompliant,
            'auditor_notes' => $reportData['auditor_notes'] ?? ($finding->auditor_notes ?? ''),
            'control_id' => $control->control_id ?? '',
            'compliance_status' => $isCompliant ? 'Compliant' : 'Non-Compliant',
        ];

        // If compliant, emphasize compliant description
        if ($isCompliant) {
            $output['compliance_detail'] = $reportData['compliant_description'] ?? 'Evidence meets requirements';
        }

        return $output;
    }
}
