<?php

namespace App\Modules\Compliance\Services;

use App\Models\AssessmentFinding;
use App\Modules\Compliance\Events\ControlTestCompleted;
use App\Modules\Compliance\Models\ControlTest;
use App\Modules\Compliance\Models\FrameworkVersion;
use Illuminate\Support\Collection;

class ControlTestService
{
    public function execute(
        int $controlId,
        int $testedBy,
        string $testType,
        string $result,
        ?float $score = null,
        ?string $notes = null,
        ?string $evidenceSummary = null,
        ?int $frameworkVersionId = null,
        ?int $findingId = null,
        ?int $projectAssessmentId = null
    ): ControlTest {
        $test = ControlTest::create([
            'control_id' => $controlId,
            'tested_by' => $testedBy,
            'test_type' => $testType,
            'test_date' => now(),
            'result' => $result,
            'score' => $score,
            'notes' => $notes,
            'evidence_summary' => $evidenceSummary,
            'framework_version_id' => $frameworkVersionId,
            'assessment_finding_id' => $findingId,
        ]);

        if (!$findingId && in_array($result, ['fail', 'partial', 'error'])) {
            $finding = $this->createFindingFromTest($test, $projectAssessmentId);
            $test->update(['assessment_finding_id' => $finding->id]);
            $test->load('assessmentFinding');
        }

        event(new ControlTestCompleted($test, $test->assessmentFinding, $result));

        return $test;
    }

    public function createFindingFromTest(ControlTest $test, ?int $projectAssessmentId = null): AssessmentFinding
    {
        $control = $test->control;

        return AssessmentFinding::create([
            'project_assessment_id' => $projectAssessmentId,
            'source_type' => get_class($test),
            'source_id' => $test->id,
            'compliance_state' => $test->result === 'partial' ? 'partially_compliant' : 'non_compliant',
            'status' => 'Open',
            'framework_control_id' => $control->riskControlMappings()->first()?->framework_control_id,
            'observation' => sprintf('Control test failed: %s — %s', $control->name, $test->notes ?? ''),
            'is_compliant' => false,
            'is_applicable' => true,
            'due_date' => now()->addDays(config('compliance.sla_defaults.resolution_hours', 168) / 24),
        ]);
    }

    public function getFailedTests(?int $projectId = null): Collection
    {
        $query = ControlTest::failed()->with('control');

        if ($projectId) {
            $query->whereHas('control', fn ($q) => $q->whereHas('riskControlMappings', fn ($q2) => $q2->whereHas('risk', fn ($q3) => $q3->where('project_id', $projectId))));
        }

        return $query->latest()->get();
    }

    public function getHistory(int $controlId): Collection
    {
        return ControlTest::where('control_id', $controlId)
            ->with('testedBy')
            ->latest()
            ->get();
    }
}
