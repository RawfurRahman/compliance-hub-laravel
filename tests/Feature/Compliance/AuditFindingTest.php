<?php

namespace Tests\Feature\Compliance;

use App\Models\AssessmentFinding;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\User;
use App\Modules\Compliance\Models\AuditFinding;
use App\Modules\Compliance\Services\AuditFindingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditFindingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::create(['name' => 'Audit Test', 'module_type' => 'compliance', 'user_id' => $this->user->id]);
    }

    public function test_can_create_audit_finding(): void
    {
        $service = app(AuditFindingService::class);
        $finding = $service->create([
            'project_id' => $this->project->id,
            'finding_reference' => 'AUD-2026-001',
            'title' => 'Missing encryption policy',
            'audit_date' => now()->toDateString(),
            'severity' => 'high',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('comp_audit_findings', ['finding_reference' => 'AUD-2026-001']);
        $this->assertEquals('high', $finding->severity);
    }

    public function test_audit_finding_is_overdue(): void
    {
        $finding = AuditFinding::create([
            'project_id' => $this->project->id,
            'finding_reference' => 'AUD-2026-002',
            'title' => 'Overdue finding',
            'audit_date' => now()->subDays(10)->toDateString(),
            'severity' => 'medium',
            'status' => 'open',
            'due_date' => now()->subDays(1),
        ]);

        $this->assertTrue($finding->is_overdue);
    }

    public function test_audit_finding_not_overdue_when_resolved(): void
    {
        $finding = AuditFinding::create([
            'project_id' => $this->project->id,
            'finding_reference' => 'AUD-2026-003',
            'title' => 'Resolved finding',
            'audit_date' => now()->subDays(10)->toDateString(),
            'severity' => 'low',
            'status' => 'closed',
            'due_date' => now()->subDays(1),
        ]);

        $this->assertFalse($finding->is_overdue);
    }

    public function test_can_close_audit_finding(): void
    {
        $service = app(AuditFindingService::class);
        $finding = $service->create([
            'project_id' => $this->project->id,
            'finding_reference' => 'AUD-2026-004',
            'title' => 'Test finding',
            'audit_date' => now()->toDateString(),
            'severity' => 'critical',
            'status' => 'open',
        ]);

        $closed = $service->close($finding->id, 'All issues addressed');
        $this->assertEquals('closed', $closed->status);
    }

    public function test_can_link_to_assessment_finding(): void
    {
        $framework = Framework::create(['name' => 'PCI DSS', 'slug' => 'pci_dss', 'is_active' => true]);
        $fc = FrameworkControl::create(['framework_id' => $framework->id, 'control_id' => 'PCI-1.1', 'domain' => 'General', 'requirement_description' => 'Test requirement']);

        $assessment = ProjectAssessment::create([
            'project_id' => $this->project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'overall_status' => 'in_progress',
        ]);

        $service = app(AuditFindingService::class);
        $auditFinding = $service->create([
            'project_id' => $this->project->id,
            'finding_reference' => 'AUD-2026-005',
            'title' => 'Link test',
            'audit_date' => now()->toDateString(),
            'severity' => 'medium',
            'status' => 'open',
        ]);

        $assessmentFinding = AssessmentFinding::create([
            'project_assessment_id' => $assessment->id,
            'framework_control_id' => $fc->id,
            'status' => 'Open',
            'observation' => 'Linked finding',
            'is_compliant' => false,
            'is_applicable' => true,
        ]);

        $result = $service->linkToFinding($auditFinding->id, $assessmentFinding->id);

        $this->assertArrayHasKey('audit_finding', $result);
        $this->assertArrayHasKey('assessment_finding', $result);
        $this->assertNotNull($assessmentFinding->fresh()->source_type);
    }
}
