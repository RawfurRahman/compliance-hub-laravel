<?php

namespace App\Services;

use App\Models\AssessmentFinding;
use App\Models\EvidenceFile;
use App\Models\EvidenceWorkflowLog;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class EvidenceTrackerService
{
    private GapAssessmentReportService $gapReportService;

    private RiskAutoCreationService $riskAutoService;

    public function __construct(
        GapAssessmentReportService $gapReportService,
        RiskAutoCreationService $riskAutoService
    ) {
        $this->gapReportService = $gapReportService;
        $this->riskAutoService = $riskAutoService;
    }

    public function submitForReview(EvidenceFile $evidence, array $reportData): EvidenceFile
    {
        if ($evidence->ai_analysis_status !== 'awaiting_review') {
            throw new \RuntimeException('AI analysis must be complete and awaiting review.');
        }

        $fromStatus = $evidence->tracker_status;
        $evidence->update([
            'analysis_report_data' => $reportData,
            'tracker_status' => 'submitted',
        ]);

        $this->logWorkflow($evidence, $fromStatus, 'submitted', 'Submitted for auditor review');

        return $evidence->fresh();
    }

    public function approveAnalysis(EvidenceFile $evidence, array $reportData): EvidenceFile
    {
        if (! in_array($evidence->tracker_status, ['submitted', 'awaiting_review'])) {
            throw new \RuntimeException('Evidence must be submitted for review before approval.');
        }

        $fromStatus = $evidence->tracker_status;

        $evidence->update([
            'ai_analysis_status' => 'approved',
            'ai_analysis_approved_by' => Auth::id(),
            'ai_analysis_approved_at' => now(),
            'analysis_report_data' => array_merge($evidence->analysis_report_data ?? [], $reportData),
            'tracker_status' => 'approved',
        ]);

        $reportSection = $this->gapReportService->generateReportSection($evidence);
        $evidence->update(['report_section_data' => $reportSection]);

        if ($evidence->hitl_status === 'accepted') {
            $this->autoComplyFinding($evidence);
        }

        $this->logWorkflow($evidence, $fromStatus, 'approved', 'AI analysis approved with structured data');

        return $evidence->fresh();
    }

    public function sendToGapAssessment(EvidenceFile $evidence): AssessmentFinding
    {
        if ($evidence->tracker_status !== 'approved' && $evidence->tracker_status !== 'gap_assessment_sent') {
            throw new \RuntimeException('Evidence must be approved before sending to gap assessment.');
        }

        $fromStatus = $evidence->tracker_status;
        $finding = $this->gapReportService->sendToGapAssessment($evidence);

        $evidence->update([
            'tracker_status' => 'gap_assessment_sent',
            'gap_assessment_sent_at' => now(),
        ]);

        $this->logWorkflow($evidence, $fromStatus, 'gap_assessment_sent', "Sent to gap assessment (finding_id: {$finding->id})");

        return $finding;
    }

    public function passToGapAssessment(EvidenceFile $evidence): array
    {
        if (! in_array($evidence->tracker_status, ['approved', 'gap_assessment_sent'])
            && $evidence->hitl_status !== 'accepted') {
            throw new \RuntimeException('Evidence must be accepted/approved before passing to gap assessment report.');
        }

        $reportData = $evidence->analysis_report_data ?? [];

        // Update evidence with gap assessment input fields
        $evidence->update([
            'tracker_status' => 'gap_assessment_sent',
            'gap_assessment_sent_at' => now(),
            'analysis_report_data' => array_merge($evidence->analysis_report_data ?? [], [
                'gap_category' => $reportData['gap_category'] ?? '',
                'non_compliant_details' => $reportData['non_compliant_details'] ?? '',
                'compliant_description' => $reportData['compliant_description'] ?? '',
                'remediation_plan' => $reportData['remediation_plan'] ?? '',
                'evidence_provided' => $reportData['evidence_provided'] ?? '',
                'test_results' => $reportData['test_results'] ?? '',
                'meets_standard' => $reportData['meets_standard'] ?? false,
                'auditor_notes' => $reportData['auditor_notes'] ?? '',
            ]),
        ]);

        // Create/update the assessment finding
        $finding = $this->gapReportService->sendToGapAssessment($evidence);

        $this->logWorkflow($evidence, 'passToGapAssessment', 'gap_assessment_sent', "Passed to gap assessment report (finding_id: {$finding->id})");

        // Return the gap assessment report data
        return $this->getGapAssessmentReportData($evidence, $finding);
    }

    private function getGapAssessmentReportData(EvidenceFile $evidence, ?AssessmentFinding $finding = null): array
    {
        $reportData = $evidence->analysis_report_data ?? [];
        $finding = $finding ?? AssessmentFinding::where('source_type', 'evidence')
            ->where('source_id', $evidence->id)
            ->where('framework_control_id', $evidence->framework_control_id)
            ->first();

        $control = $evidence->frameworkControl;

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
        ];

        // If compliant, include compliant description and remove non-compliant fields emphasis
        if ($isCompliant) {
            $output['compliance_status'] = 'Compliant';
            $output['compliant_description'] = $reportData['compliant_description'] ?? 'Evidence meets requirements';
            $output['non_compliant_details'] = '';
        } else {
            $output['compliance_status'] = 'Non-Compliant';
        }

        return $output;
    }

    public function passToFinalReport(EvidenceFile $evidence): EvidenceFile
    {
        if (! in_array($evidence->tracker_status, ['approved', 'gap_assessment_sent'])) {
            throw new \RuntimeException('Evidence must be approved before passing to final report.');
        }

        $fromStatus = $evidence->tracker_status;

        $this->gapReportService->syncToFinalReport($evidence);

        $evidence->update([
            'tracker_status' => 'final_report_ready',
            'final_report_flagged_at' => now(),
        ]);

        $this->logWorkflow($evidence, $fromStatus, 'final_report_ready', 'Passed to final report');

        return $evidence->fresh();
    }

    public function autoCreateRisk(EvidenceFile $evidence): RiskRegister
    {
        if ($evidence->tracker_status === 'risk_created') {
            throw new \RuntimeException('Risk already created for this evidence.');
        }

        $reportData = $evidence->analysis_report_data ?? [];
        $isCompliant = $reportData['is_compliant'] ?? false;

        if ($isCompliant) {
            throw new \RuntimeException('Cannot create risk for a compliant finding.');
        }

        $fromStatus = $evidence->tracker_status;

        $risk = $this->riskAutoService->createRiskFromGap($evidence);

        $evidence->update([
            'tracker_status' => 'risk_created',
            'risk_register_created_at' => now(),
        ]);

        $this->logWorkflow($evidence, $fromStatus, 'risk_created', "Risk auto-created (risk_id: {$risk->id})");

        return $risk;
    }

    public function rejectAnalysis(EvidenceFile $evidence, string $reason): EvidenceFile
    {
        if (! in_array($evidence->tracker_status, ['submitted', 'awaiting_review'])) {
            throw new \RuntimeException('Can only reject evidence awaiting or submitted for review.');
        }

        $fromStatus = $evidence->tracker_status;

        $evidence->feedbacks()->create([
            'user_id' => Auth::id(),
            'message' => '[Tracker Rejection] '.$reason,
        ]);

        $evidence->update([
            'tracker_status' => 'rejected',
            'ai_analysis_status' => 'awaiting_review',
        ]);

        $this->logWorkflow($evidence, $fromStatus, 'rejected', $reason);

        return $evidence->fresh();
    }

    public function getTrackerDashboard(Project $project): array
    {
        $evidenceFiles = $project->evidenceFiles()
            ->with(['frameworkControl', 'user', 'approvedBy', 'workflowLogs'])
            ->forTracker()
            ->latest()
            ->get();

        $statusCounts = [
            'pending' => 0,
            'awaiting_review' => 0,
            'submitted' => 0,
            'approved' => 0,
            'gap_assessment_sent' => 0,
            'final_report_ready' => 0,
            'risk_created' => 0,
            'rejected' => 0,
        ];

        foreach ($evidenceFiles as $ef) {
            $status = $ef->tracker_status;
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }
        }

        $files = $evidenceFiles->map(function ($ef) {
            $reportData = $ef->analysis_report_data ?? [];

            return [
                'id' => $ef->id,
                'original_filename' => $ef->original_filename,
                'tracker_status' => $ef->tracker_status,
                'ai_analysis_status' => $ef->ai_analysis_status,
                'risk_rating' => $reportData['risk_rating'] ?? null,
                'is_compliant' => $reportData['is_compliant'] ?? null,
                'gap_description' => $reportData['gap_description'] ?? '',
                'control_ref' => $reportData['control_ref'] ?? ($ef->frameworkControl->control_id ?? ''),
                'requirement_description' => $reportData['requirement_description'] ?? ($ef->frameworkControl->requirement_description ?? ''),
                'framework_control_id' => $ef->framework_control_id,
                'uploaded_by' => $ef->user?->username,
                'approved_by' => $ef->approvedBy?->username,
                'created_at' => $ef->created_at->toDateTimeString(),
                'gap_assessment_sent_at' => $ef->gap_assessment_sent_at?->toDateTimeString(),
                'final_report_flagged_at' => $ef->final_report_flagged_at?->toDateTimeString(),
                'risk_register_created_at' => $ef->risk_register_created_at?->toDateTimeString(),
                'can_send_to_gap' => in_array($ef->tracker_status, ['approved']),
                'can_pass_to_final' => in_array($ef->tracker_status, ['approved', 'gap_assessment_sent']),
                'can_create_risk' => in_array($ef->tracker_status, ['approved', 'gap_assessment_sent', 'final_report_ready'])
                    && ! ($reportData['is_compliant'] ?? false),
                'workflow_logs' => $ef->workflowLogs->sortByDesc('created_at')->values()->toArray(),
            ];
        })->values()->toArray();

        return [
            'evidence_files' => $files,
            'status_counts' => $statusCounts,
            'total' => $evidenceFiles->count(),
        ];
    }

    public function logWorkflow(EvidenceFile $evidence, ?string $fromStatus, string $toStatus, ?string $notes = null): EvidenceWorkflowLog
    {
        return EvidenceWorkflowLog::create([
            'evidence_file_id' => $evidence->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'user_id' => Auth::id(),
            'notes' => $notes,
        ]);
    }

    private function autoComplyFinding(EvidenceFile $evidence): void
    {
        if (! $evidence->framework_control_id) {
            return;
        }

        AssessmentFinding::where('framework_control_id', $evidence->framework_control_id)
            ->whereHas('projectAssessment', function ($q) use ($evidence) {
                $q->where('project_id', $evidence->project_id);
            })
            ->where('is_compliant', false)
            ->update(['is_compliant' => true]);
    }
}
