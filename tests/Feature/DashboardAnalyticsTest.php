<?php

namespace Tests\Feature;

use App\Models\AssessmentFinding;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(?string $role): User
    {
        $user = User::factory()->create();
        if ($role) {
            $user->roles()->create(['name' => $role]);
        }
        return $user;
    }

    /**
     * Build a framework with a Gap assessment and `$compliant` of `$total`
     * findings marked compliant. Returns the framework.
     *
     * Marking findings compliant fires AssessmentService::syncFinding(),
     * which auto-creates the Final assessment only once the Gap hits 100%.
     */
    private function seedFramework(string $name, string $slug, int $total, int $compliant): Framework
    {
        $framework = Framework::create([
            'name'      => $name,
            'slug'      => $slug,
            'is_active' => true,
        ]);

        $project = Project::create([
            'name'        => $name . ' Project',
            'module_type' => $slug,
            'user_id'     => User::factory()->create()->id,
        ]);

        for ($i = 1; $i <= $total; $i++) {
            FrameworkControl::create([
                'framework_id'            => $framework->id,
                'control_id'              => "C.$i",
                'domain'                  => 'General',
                'requirement_description' => "Control $i",
            ]);
        }

        $gap = ProjectAssessment::create([
            'project_id'   => $project->id,
            'framework_id' => $framework->id,
            'type'         => 'Gap',
            'start_date'   => now(),
            'end_date'     => now()->addMonth(),
        ]);
        app(AssessmentService::class)->initialize($gap);

        $gap->findings()->take($compliant)->get()->each(function ($finding) {
            $finding->update(['is_compliant' => true, 'status' => 'Closed']);
        });

        return $framework;
    }

    private const ENDPOINTS = [
        'kpis',
        'heatmap',
        'top-risks',
        'inherent-vs-residual',
        'control-effectiveness',
        'compliance-scorecard',
        'maturity-score',
        'risk-by-department',
        'issues-and-remediation',
        'risk-acceptance-split',
    ];

    public function test_guest_is_redirected_to_login()
    {
        $this->get('/dashboard/kpis')->assertRedirect('/login');
    }

    public function test_customer_role_is_forbidden()
    {
        $user = $this->userWithRole('Customer');

        foreach (self::ENDPOINTS as $endpoint) {
            $this->actingAs($user)->get("/dashboard/{$endpoint}")->assertForbidden();
        }
    }

    public function test_auditor_can_access_every_endpoint()
    {
        $user = $this->userWithRole('Auditor');
        $this->seedFramework('ISO 27001', 'iso_27001', 4, 2);

        foreach (self::ENDPOINTS as $endpoint) {
            $this->actingAs($user)->get("/dashboard/{$endpoint}")->assertOk();
        }
    }

    public function test_admin_can_access_every_endpoint()
    {
        $user = $this->userWithRole('Admin');
        $this->seedFramework('ISO 27001', 'iso_27001', 4, 2);

        foreach (self::ENDPOINTS as $endpoint) {
            $this->actingAs($user)->get("/dashboard/{$endpoint}")->assertOk();
        }
    }

    public function test_kpis_shape()
    {
        $user = $this->userWithRole('Admin');
        $this->seedFramework('ISO 27001', 'iso_27001', 4, 2);

        $this->actingAs($user)->get('/dashboard/kpis')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'projects', 'frameworks', 'total_controls', 'compliant',
                    'open_findings', 'overdue_findings', 'compliance_pct',
                ],
            ]);
    }

    public function test_heatmap_shape()
    {
        $user = $this->userWithRole('Admin');
        $this->seedFramework('ISO 27001', 'iso_27001', 4, 2);

        $this->actingAs($user)->get('/dashboard/heatmap')
            ->assertOk()
            ->assertJsonStructure(['data' => [['likelihood', 'impact', 'count']]]);
    }

    public function test_maturity_score_shape()
    {
        $user = $this->userWithRole('Admin');
        $this->seedFramework('ISO 27001', 'iso_27001', 4, 2);

        $this->actingAs($user)->get('/dashboard/maturity-score')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'composite', 'risk_management', 'control_design',
                    'remediation_velocity', 'evidence_audit',
                ],
            ]);
    }

    public function test_control_effectiveness_shape()
    {
        $user = $this->userWithRole('Admin');
        $this->seedFramework('ISO 27001', 'iso_27001', 4, 2);

        $this->actingAs($user)->get('/dashboard/control-effectiveness')
            ->assertOk()
            ->assertJsonStructure(['data' => ['effective', 'partial', 'ineffective']]);
    }

    public function test_compliance_scorecard_reports_phase()
    {
        $user = $this->userWithRole('Admin');
        // 4 of 4 compliant -> Gap == 100% -> Final auto-created at 100% -> final_done.
        $this->seedFramework('Done Framework', 'done_fw', 4, 4);

        $this->actingAs($user)->get('/dashboard/compliance-scorecard')
            ->assertOk()
            ->assertJsonStructure(['data' => [['framework', 'percentage', 'phase', 'fully_compliant']]])
            ->assertJsonFragment(['framework' => 'Done Framework', 'phase' => 'final_done', 'fully_compliant' => true]);
    }

    /**
     * The headline guarantee: a framework still stuck in the Gap phase must
     * never surface as fully compliant, even when its raw Gap percentage is
     * high. 9 of 10 compliant = 90% (high) but < 100%, so no Final exists and
     * the phase stays gap_in_progress with fully_compliant = false.
     */
    public function test_high_gap_percentage_is_not_fully_compliant()
    {
        $user = $this->userWithRole('Admin');
        $this->seedFramework('Stuck Framework', 'stuck_fw', 10, 9);

        $response = $this->actingAs($user)->get('/dashboard/compliance-scorecard')->assertOk();

        $row = collect($response->json('data'))
            ->firstWhere('framework', 'Stuck Framework');

        $this->assertNotNull($row);
        $this->assertSame('gap_in_progress', $row['phase']);
        $this->assertFalse($row['fully_compliant']);
        $this->assertGreaterThanOrEqual(80, $row['percentage']); // raw number is high

        // No Final assessment should have been created for a sub-100% Gap.
        $this->assertDatabaseMissing('project_assessments', [
            'framework_id' => $row ? Framework::where('name', 'Stuck Framework')->first()->id : null,
            'type'         => 'Final',
        ]);
    }

    public function test_heavy_endpoints_are_cached()
    {
        $user = $this->userWithRole('Admin');
        Cache::flush();

        // No filters = empty array. Key is dashboard.kpis.<md5 of "[]">
        $emptyFilterKey = md5(json_encode([]));

        $this->actingAs($user)->get('/dashboard/kpis')->assertOk();
        $this->assertTrue(Cache::has('dashboard.kpis.' . $emptyFilterKey));

        $this->actingAs($user)->get('/dashboard/heatmap')->assertOk();
        $this->assertTrue(Cache::has('dashboard.heatmap.' . $emptyFilterKey));
    }
}
