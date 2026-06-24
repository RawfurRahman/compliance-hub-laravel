<?php

namespace Tests\Feature\Compliance;

use App\Models\AssessmentFinding;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\User;
use App\Modules\Compliance\Models\SLATracker;
use App\Modules\Compliance\Services\SLATrackerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SLATrackerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AssessmentFinding $finding;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $project = Project::create(['name' => 'SLA Test', 'module_type' => 'compliance', 'user_id' => $this->user->id]);
        $framework = Framework::create(['name' => 'PCI DSS', 'slug' => 'pci_dss', 'is_active' => true]);
        $fc = FrameworkControl::create(['framework_id' => $framework->id, 'control_id' => 'PCI-1.1', 'domain' => 'General', 'requirement_description' => 'Test requirement']);
        $assessment = ProjectAssessment::create([
            'project_id' => $project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'overall_status' => 'in_progress',
        ]);
        $this->finding = AssessmentFinding::create([
            'project_assessment_id' => $assessment->id,
            'framework_control_id' => $fc->id,
            'status' => 'Open',
            'observation' => 'SLA test finding',
            'is_compliant' => false,
            'is_applicable' => true,
            'compliance_state' => 'non_compliant',
        ]);
    }

    public function test_can_create_sla(): void
    {
        $service = app(SLATrackerService::class);
        $sla = $service->createFor($this->finding, 'resolution', now()->addDays(7));

        $this->assertDatabaseHas('comp_sla_trackers', ['id' => $sla->id]);
        $this->assertEquals('active', $sla->status);
    }

    public function test_check_breaches_detects_overdue(): void
    {
        $service = app(SLATrackerService::class);
        $service->createFor($this->finding, 'response', now()->subHour());

        $breached = $service->checkBreaches();
        $this->assertCount(1, $breached);
        $this->assertEquals('breached', $breached->first()->status);
    }

    public function test_resolve_sla(): void
    {
        $service = app(SLATrackerService::class);
        $sla = $service->createFor($this->finding, 'response', now()->addDays(3));

        $resolved = $service->resolve($sla);
        $this->assertEquals('met', $resolved->status);
    }

    public function test_get_stats(): void
    {
        $service = app(SLATrackerService::class);
        $service->createFor($this->finding, 'response', now()->addDays(3));
        $service->createFor($this->finding, 'resolution', now()->addDays(7));

        $stats = $service->getStats();
        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(2, $stats['active']);
        $this->assertEquals(0, $stats['breached']);
    }

    public function test_polymorphic_relationship(): void
    {
        $sla = SLATracker::create([
            'trackable_type' => get_class($this->finding),
            'trackable_id' => $this->finding->id,
            'sla_type' => 'response',
            'deadline_at' => now()->addDays(3),
            'status' => 'active',
        ]);

        $this->assertInstanceOf(AssessmentFinding::class, $sla->trackable);
        $this->assertEquals($this->finding->id, $sla->trackable->id);
    }
}
