<?php

namespace App\Services;

use App\Models\ProjectAssessment;
use App\Models\AssessmentFinding;
use App\Models\FrameworkControl;

class AssessmentService
{
    /**
     * Initialize an assessment by creating finding records for all controls of the framework.
     */
    public function initialize(ProjectAssessment $assessment): void
    {
        $controls = FrameworkControl::where('framework_id', $assessment->framework_id)->get();

        foreach ($controls as $control) {
            AssessmentFinding::firstOrCreate([
                'project_assessment_id' => $assessment->id,
                'framework_control_id'  => $control->id,
            ], [
                'status'         => 'Open',
                'risk_rating'    => 'None',
                'is_compliant'   => false,
            ]);
        }
    }

    /**
     * Synchronize a finding from Gap assessment to Final assessment.
     */
    public function syncFinding(AssessmentFinding $finding): void
    {
        $assessment = $finding->projectAssessment;

        if (!$assessment || $assessment->type !== 'Gap') {
            return;
        }

        // Unset findings relationship to avoid cached collection state
        $assessment->unsetRelation('findings');

        // Calculate stats for the Gap assessment
        $stats = $assessment->stats();
        $isCompleted = $stats['compliancePct'] == 100;

        if ($isCompleted) {
            // Find or create the Final assessment
            $finalAssessment = ProjectAssessment::firstOrCreate([
                'project_id'   => $assessment->project_id,
                'framework_id' => $assessment->framework_id,
                'type'         => 'Final',
            ], [
                'start_date'     => $assessment->start_date ?? now(),
                'end_date'       => $assessment->end_date ?? now(),
                'cloned_from_id' => $assessment->id,
            ]);

            // Deep-clone/sync ALL findings from the Gap assessment to the Final assessment
            $gapFindings = $assessment->findings;
            foreach ($gapFindings as $f) {
                $clonedFinding = AssessmentFinding::updateOrCreate(
                    [
                        'project_assessment_id' => $finalAssessment->id,
                        'framework_control_id'  => $f->framework_control_id,
                    ],
                    [
                        'status'                 => $f->status,
                        'risk_rating'            => $f->risk_rating,
                        'observation'            => $f->observation,
                        'gap_description'        => $f->gap_description,
                        'impact'                 => $f->impact,
                        'recommendation'         => $f->recommendation,
                        'is_compliant'           => $f->is_compliant,
                        'cloned_from_finding_id' => $f->id,
                    ]
                );

                // Sync evidence pivot records
                $evidenceIds = $f->evidence()->pluck('evidence.id')->toArray();
                $clonedFinding->evidence()->sync($evidenceIds);
            }
        } else {
            // If Gap assessment is no longer 100% compliant, delete the Final assessment
            $finalAssessments = ProjectAssessment::where('project_id', $assessment->project_id)
                ->where('framework_id', $assessment->framework_id)
                ->where('type', 'Final')
                ->get();

            foreach ($finalAssessments as $final) {
                foreach ($final->findings as $f) {
                    $f->evidence()->detach();
                    $f->delete();
                }
                $final->delete();
            }
        }
    }

    /**
     * Delete any cloned findings when the parent Gap finding is deleted.
     */
    public function deleteClonedFinding(AssessmentFinding $finding): void
    {
        AssessmentFinding::where('cloned_from_finding_id', $finding->id)->delete();
    }
}
