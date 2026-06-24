<?php

namespace App\Services;

use App\Models\AssessmentFinding;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\ProjectAssessment;
use Illuminate\Support\Str;

class GapAssessmentService
{
    public function findOrCreateAssessment(Project $project, Framework $framework): ProjectAssessment
    {
        return ProjectAssessment::firstOrCreate(
            [
                'project_id'   => $project->id,
                'framework_id' => $framework->id,
                'type'         => 'Gap',
            ],
            [
                'start_date' => now(),
                'end_date'   => now()->addMonths(3),
            ]
        );
    }

    public function initialize(ProjectAssessment $assessment): int
    {
        $controls = FrameworkControl::where('framework_id', $assessment->framework_id)->get();
        $created = 0;

        foreach ($controls as $control) {
            AssessmentFinding::firstOrCreate(
                [
                    'project_assessment_id' => $assessment->id,
                    'framework_control_id'  => $control->id,
                ],
                [
                    'status'       => 'Open',
                    'risk_rating'  => 'None',
                    'is_compliant' => false,
                ]
            );
            $created++;
        }

        return $created;
    }

    public function getGroupedFindings(ProjectAssessment $assessment): array
    {
        $assessment->load(['findings.frameworkControl', 'findings.evidence']);
        $assessment->findings->loadCount('evidence');
        $findings = $assessment->findings;

        $unknown = collect();
        $groups  = [];

        foreach ($findings as $finding) {
            $control = $finding->frameworkControl;
            $domain  = $control ? $control->domain : 'Unknown';

            if ($domain === 'Unknown' || empty($domain)) {
                $unknown->push($finding);
                continue;
            }

            if (!isset($groups[$domain])) {
                $groups[$domain] = collect();
            }
            $groups[$domain]->push($finding);
        }

        $ordered = [];
        $priority = ['Organizational', 'People', 'Physical', 'Technological'];
        foreach ($priority as $name) {
            if (isset($groups[$name])) {
                $ordered[$name] = $groups[$name];
                unset($groups[$name]);
            }
        }
        foreach ($groups as $name => $items) {
            $ordered[$name] = $items;
        }
        if ($unknown->isNotEmpty()) {
            $ordered['Other'] = $unknown;
        }

        return $ordered;
    }

    public function getGroupedStats(array $groupedFindings): array
    {
        $groups = [];

        foreach ($groupedFindings as $domain => $findings) {
            $total       = $findings->count();
            $compliant   = $findings->where('is_compliant', true)->count();
            $high        = $findings->where('risk_rating', 'High')->count();
            $medium      = $findings->where('risk_rating', 'Medium')->count();
            $low         = $findings->where('risk_rating', 'Low')->count();
            $inProgress  = $findings->where('status', 'In Progress')->count();
            $closed      = $findings->where('status', 'Closed')->count();
            $open        = $findings->where('status', 'Open')->count();
            $compliancePct = $total > 0 ? round(($compliant / $total) * 100, 1) : 0;
            $progressScore  = $total > 0 ? round((($inProgress * 0.5) + ($closed * 1.0)) / $total * 100, 1) : 0;

            $groups[$domain] = compact(
                'total', 'compliant', 'high', 'medium', 'low',
                'inProgress', 'closed', 'open', 'compliancePct', 'progressScore'
            );
        }

        return $groups;
    }

    public function updateFinding(AssessmentFinding $finding, array $data): AssessmentFinding
    {
        $allowed = ['status', 'risk_rating', 'is_compliant', 'observation', 'gap_description', 'impact', 'recommendation', 'due_date', 'is_applicable'];

        $filtered = array_intersect_key($data, array_flip($allowed));

        if (isset($filtered['is_compliant'])) {
            $filtered['is_compliant'] = filter_var($filtered['is_compliant'], FILTER_VALIDATE_BOOLEAN);
        }

        $finding->update($filtered);
        $finding->refresh();

        return $finding;
    }

    public function batchUpdate(ProjectAssessment $assessment, array $findingsData): int
    {
        $count = 0;
        foreach ($findingsData as $id => $data) {
            $finding = AssessmentFinding::where('project_assessment_id', $assessment->id)
                ->where('id', $id)
                ->first();

            if ($finding) {
                $this->updateFinding($finding, $data);
                $count++;
            }
        }
        return $count;
    }

    public function getEvidenceFiles(AssessmentFinding $finding): array
    {
        $legacy = $finding->evidence->map(fn ($e) => [
            'id'   => $e->id,
            'name' => $e->name,
            'type' => 'legacy',
        ]);

        $evidenceFiles = \App\Models\EvidenceFile::where('project_id', $finding->projectAssessment->project_id)
            ->where('framework_control_id', $finding->framework_control_id)
            ->where('hitl_status', 'accepted')
            ->get()
            ->map(fn ($ef) => [
                'id'   => $ef->id,
                'name' => $ef->original_filename,
                'type' => 'evidence_file',
            ]);

        return $legacy->concat($evidenceFiles)->values()->toArray();
    }
}
