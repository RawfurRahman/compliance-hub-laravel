<?php

namespace App\Services;

use App\Models\AssessmentFinding;
use App\Models\EvidenceFile;
use App\Models\ProjectAssessment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AiGapConsolidationService
{
    private const SEVERITY_MAP = [
        'high' => 'High',
        'medium' => 'Medium',
        'low' => 'Low',
    ];

    public function consolidate(EvidenceFile $evidence): array
    {
        if ($evidence->ai_analysis_status !== 'approved') {
            throw new \RuntimeException('Cannot consolidate: AI analysis is not approved.');
        }

        $gaps = $evidence->ai_gaps;
        if (empty($gaps) || ! is_array($gaps)) {
            return [];
        }

        if (! $evidence->framework_control_id) {
            Log::info("AiGapConsolidation: evidence_file_id {$evidence->id} has no framework_control_id, skipping");

            return [];
        }

        $control = $evidence->frameworkControl;
        if (! $control) {
            Log::warning("AiGapConsolidation: FrameworkControl {$evidence->framework_control_id} not found");

            return [];
        }

        $assessment = ProjectAssessment::where('project_id', $evidence->project_id)
            ->where('framework_id', $control->framework_id)
            ->where('type', 'Gap')
            ->first();

        if (! $assessment) {
            Log::info("AiGapConsolidation: No Gap assessment found for project {$evidence->project_id}, framework {$control->framework_id}");

            return [];
        }

        $userId = Auth::id();
        $created = [];

        foreach ($gaps as $gap) {
            $gapText = $gap['gap'] ?? '';
            $severity = $gap['severity'] ?? 'medium';
            $riskRating = self::SEVERITY_MAP[$severity] ?? 'Medium';

            if (empty($gapText)) {
                continue;
            }

            $finding = AssessmentFinding::firstOrCreate(
                [
                    'project_assessment_id' => $assessment->id,
                    'framework_control_id' => $evidence->framework_control_id,
                ],
                [
                    'status' => 'Open',
                    'risk_rating' => $riskRating,
                    'gap_description' => $gapText,
                    'observation' => $gapText,
                    'is_compliant' => false,
                    'is_applicable' => true,
                    'source_type' => 'evidence',
                    'source_id' => $evidence->id,
                ]
            );

            $existingGaps = $finding->ai_gaps ?? [];
            $existingGaps[] = [
                'gap' => $gapText,
                'severity' => $severity,
                'source_evidence_id' => $evidence->id,
            ];

            $finding->update([
                'gap_description' => $finding->gap_description ?: $gapText,
                'observation' => $finding->observation ?: $gapText,
                'risk_rating' => $finding->risk_rating === 'None' ? $riskRating : $finding->risk_rating,
                'ai_gaps' => $existingGaps,
                'ai_gaps_consolidated_at' => now(),
                'ai_gaps_consolidated_by' => $userId,
            ]);

            $created[] = $finding;
        }

        Log::info('AiGapConsolidation: Consolidated '.count($created)." gaps from evidence_file_id {$evidence->id} into assessment_id {$assessment->id}");

        return $created;
    }

    public function getPendingForProject(int $projectId): array
    {
        return EvidenceFile::where('project_id', $projectId)
            ->where('ai_analysis_status', 'approved')
            ->whereNotNull('ai_gaps')
            ->where('ai_gaps', '!=', '[]')
            ->whereRaw('json_array_length(ai_gaps) > 0')
            ->whereNull('ai_gaps_consolidated_at')
            ->with('frameworkControl')
            ->get()
            ->toArray();
    }
}
