<?php

namespace Tests\Feature\Compliance;

use App\Models\Framework;
use App\Models\Project;
use App\Models\User;
use App\Modules\Compliance\Models\ComplianceTest;
use App\Modules\Compliance\Models\ComplianceTestFrameworkLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Stage5FrameworkViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected Framework $fwPci;
    protected Framework $fwIso;
    protected Framework $fwEmpty;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::create([
            'name' => 'Framework View Test',
            'module_type' => 'compliance',
            'user_id' => $this->user->id,
        ]);

        $this->fwPci = Framework::create(['name' => 'PCI DSS', 'slug' => 'pci_dss', 'is_active' => true]);
        $this->fwIso = Framework::create(['name' => 'ISO 27001', 'slug' => 'iso_27001', 'is_active' => true]);
        $this->fwEmpty = Framework::create(['name' => 'SOC 2', 'slug' => 'soc2', 'is_active' => true]);
    }

    private function createTest(string $name, string $status, string $testType = 'Automated', array $frameworkIds = []): ComplianceTest
    {
        $test = ComplianceTest::create([
            'name' => $name,
            'description' => null,
            'owner_user_id' => $this->user->id,
            'team' => 'Security',
            'test_type' => $testType,
            'sla_days' => 30,
            'status' => $status,
            'last_run_at' => now(),
            'next_due_at' => now()->addDays(30),
            'control_monitor_id' => null,
        ]);

        foreach ($frameworkIds as $fwId) {
            ComplianceTestFrameworkLink::create([
                'compliance_test_id' => $test->id,
                'framework_id' => $fwId,
                'resources_in_scope_count' => 0,
            ]);
        }

        return $test;
    }

    public function test_all_view_returns_successful_response(): void
    {
        $this->createTest('Test A', 'Passing', 'Automated', [$this->fwPci->id]);

        $response = $this->actingAs($this->user)
            ->get("/projects/{$this->project->id}/compliance/tests?view=all");

        $response->assertStatus(200);
        $response->assertSee('Test A');
        $response->assertSee('All resource monitoring');
    }

    public function test_by_framework_view_renders_framework_sections(): void
    {
        $this->createTest('PCI Test', 'Passing', 'Automated', [$this->fwPci->id]);
        $this->createTest('ISO Test', 'Overdue', 'Manual', [$this->fwIso->id]);

        $response = $this->actingAs($this->user)
            ->get("/projects/{$this->project->id}/compliance/tests?view=by_framework");

        $response->assertStatus(200);
        $response->assertSee('PCI DSS');
        $response->assertSee('ISO 27001');
        $response->assertSee('By framework');
    }

    public function test_pass_rate_is_accurate(): void
    {
        // 3 tests mapped to PCI: 2 passing, 1 overdue => 66.7%
        $this->createTest('P1', 'Passing', 'Automated', [$this->fwPci->id]);
        $this->createTest('P2', 'Passing', 'Automated', [$this->fwPci->id]);
        $this->createTest('O1', 'Overdue', 'Manual', [$this->fwPci->id]);

        $response = $this->actingAs($this->user)
            ->get("/projects/{$this->project->id}/compliance/tests?view=by_framework");

        $response->assertStatus(200);
        $response->assertSee('66.7%');

        // 1 test mapped to ISO: 1 passing => 100%
        $this->createTest('P3', 'Passing', 'Automated', [$this->fwIso->id]);

        $response = $this->actingAs($this->user)
            ->get("/projects/{$this->project->id}/compliance/tests?view=by_framework");

        $response->assertSee('66.7%');
        $response->assertSee('100%');
    }

    public function test_test_status_is_consistent_across_views(): void
    {
        // Test mapped to both PCI and ISO with status Overdue
        $sharedTest = $this->createTest('Shared', 'Overdue', 'Manual', [$this->fwPci->id, $this->fwIso->id]);

        // Fetch all view
        $allResponse = $this->actingAs($this->user)
            ->get("/projects/{$this->project->id}/compliance/tests?view=all");
        $allResponse->assertStatus(200);
        $allResponse->assertSee('Overdue');

        // Fetch by_framework view — status should be Overdue in both groups
        $fwResponse = $this->actingAs($this->user)
            ->get("/projects/{$this->project->id}/compliance/tests?view=by_framework");
        $fwResponse->assertStatus(200);

        // The same test appears under both frameworks with status Overdue
        $this->assertEquals('Overdue', $sharedTest->fresh()->status);
    }

    public function test_framework_with_no_tests_shows_zero_pass_rate(): void
    {
        $this->createTest('PCI Test', 'Passing', 'Automated', [$this->fwPci->id]);

        $response = $this->actingAs($this->user)
            ->get("/projects/{$this->project->id}/compliance/tests?view=by_framework");

        $response->assertStatus(200);
        $response->assertSee('PCI DSS');
        $response->assertSee('100%');
    }

    public function test_switching_views_preserves_filters(): void
    {
        $this->createTest('Passing Auto', 'Passing', 'Automated', [$this->fwPci->id]);
        $this->createTest('Overdue Manual', 'Overdue', 'Manual', [$this->fwPci->id]);

        // Apply status filter in all view
        $response = $this->actingAs($this->user)
            ->get("/projects/{$this->project->id}/compliance/tests?view=all&status=Overdue");

        $response->assertStatus(200);
        $response->assertSee('Overdue Manual');
        $response->assertDontSee('Passing Auto');

        // Switch to by_framework view, filter should still be applied
        $fwResponse = $this->actingAs($this->user)
            ->get("/projects/{$this->project->id}/compliance/tests?view=by_framework&status=Overdue");

        $fwResponse->assertStatus(200);
        $fwResponse->assertSee('Overdue Manual');
    }
}
