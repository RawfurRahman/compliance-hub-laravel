<?php

namespace App\Reports\Generators;

use App\Models\Framework;
use App\Models\Project;
use App\Models\ProjectAssessment;
use Illuminate\View\View;

class UnifiedAssessmentGenerator extends ReportGenerator
{
    public function generate(Project $project, string $type, array $options = []): View
    {
        $assessType = $type === 'unified_gap' ? 'Gap' : 'Final';

        $frameworkModel = Framework::where('slug', $project->module_type)
            ->where('is_active', true)
            ->firstOrFail();

        $assessment = ProjectAssessment::where('project_id', $project->id)
            ->where('framework_id', $frameworkModel->id)
            ->where('type', $assessType)
            ->firstOrFail();

        $assessment->load(['findings.frameworkControl', 'findings.evidence', 'project', 'framework']);

        $framework = $assessment->framework;
        $stats = $assessment->stats();
        $findings = $assessment->findings;

        $filters = $options['filters'] ?? null;
        $sections = $options['sections'] ?? null;

        if ($filters) {
            if (isset($filters['status']) && $filters['status'] !== 'all') {
                $isCompliant = $filters['status'] === 'compliant';
                $findings = $findings->where('is_compliant', $isCompliant);
            }
            if (isset($filters['risk']) && $filters['risk'] !== 'all') {
                $findings = $findings->where('risk_rating', $filters['risk']);
            }
        }

        if ($filters) {
            dump('Filters:', $filters, 'Findings Count:', $findings->count(), 'Finding 1 compliant:', $findings->first()->is_compliant ?? null);
        }

        return view('assessments.report-pdf', compact(
            'assessment', 'project', 'framework', 'stats', 'findings', 'sections', 'filters'
        ));
    }
}
