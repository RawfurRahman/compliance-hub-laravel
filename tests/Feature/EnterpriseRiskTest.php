<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Department;
use App\Models\User;
use App\Modules\RiskManagement\Models\RiskRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnterpriseRiskTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        if (!Schema::hasTable('risk_registers')) {
            Schema::create('risk_registers', function ($table) {
                $table->id();
                $table->unsignedBigInteger('project_id');
                $table->string('serial_no')->nullable();
                $table->string('asset_process_service')->nullable();
                $table->string('risk_owner')->nullable();
                $table->date('risk_calculation_date')->nullable();
                $table->decimal('asset_value_bdt', 10, 2)->default(0);
                $table->json('threats')->nullable();
                $table->integer('threat_level_t')->nullable();
                $table->json('vulnerabilities')->nullable();
                $table->integer('impact_confidentiality')->nullable();
                $table->integer('impact_integrity')->nullable();
                $table->integer('impact_availability')->nullable();
                $table->text('existing_control')->nullable();
                $table->integer('vulnerability_level_av')->nullable();
                $table->integer('tv_t_av')->nullable();
                $table->integer('likelihood_lh')->nullable();
                $table->integer('risk_rating_avtvlh')->nullable();
                $table->string('measurement')->nullable();
                $table->text('proposed_control')->nullable();
                $table->string('communication')->nullable();
                $table->date('implementation_from')->nullable();
                $table->date('implementation_to')->nullable();
                $table->string('implementation_status')->nullable();
                $table->string('lifecycle_status')->default('draft');
                $table->integer('residual_tv')->nullable();
                $table->integer('residual_lh')->nullable();
                $table->integer('residual_rating')->nullable();
                $table->text('follow_up_note')->nullable();
                $table->string('category')->nullable();
                $table->string('department')->nullable();
                $table->unsignedBigInteger('owner_user_id')->nullable();
                $table->unsignedBigInteger('asset_id')->nullable();
                $table->json('evidence_ids')->nullable();
                $table->string('source')->nullable();
                $table->string('legacy_source_id')->nullable();
                $table->unsignedBigInteger('assessment_finding_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->json('custom_fields')->nullable();
                $table->integer('computed_tv')->nullable();
                $table->integer('computed_risk_rating')->nullable();
                $table->integer('computed_residual_rating')->nullable();
                $table->decimal('exposure_value', 10, 2)->default(0);
                $table->boolean('is_enterprise_risk')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('user_roles')) {
            Schema::create('user_roles', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('role_id');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('assets')) {
            Schema::create('assets', function ($table) {
                $table->id();
                $table->string('name');
                $table->unsignedBigInteger('project_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('frameworks')) {
            Schema::create('frameworks', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('slug');
                $table->string('version')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('framework_controls')) {
            Schema::create('framework_controls', function ($table) {
                $table->id();
                $table->unsignedBigInteger('framework_id')->nullable();
                $table->string('control_id');
                $table->string('domain')->nullable();
                $table->text('requirement_description')->nullable();
                $table->string('control_name')->nullable();
                $table->timestamps();
            });
        }

        $this->user = User::factory()->create([
            'username' => 'enterpriseadmin',
            'email' => 'enterprise@test.com',
        ]);

        $adminRoleId = DB::table('roles')->insertGetId(['name' => 'Admin']);
        DB::table('user_roles')->insert([
            'user_id' => $this->user->id,
            'role_id' => $adminRoleId,
        ]);

        $this->project = Project::create([
            'name' => 'Enterprise Risk Test Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get(route('risk-register.enterprise', $this->project));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_access_enterprise_risks_screen()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('risk-register.enterprise', $this->project));
        $response->assertStatus(200);
        $response->assertViewIs('risk-management.enterprise-risks');
        $response->assertSee('Enterprise Risks');
    }

    public function test_enterprise_risks_screen_shows_only_enterprise_risks()
    {
        $this->actingAs($this->user);

        $enterprise = RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'ENT-001',
            'asset_process_service' => 'Board-Level Data Breach Risk',
            'risk_owner' => 'CISO',
            'risk_calculation_date' => '2026-06-01',
            'asset_value_bdt' => 5000000,
            'threat_level_t' => 5,
            'vulnerability_level_av' => 5,
            'likelihood_lh' => 4,
            'risk_rating_avtvlh' => 100,
            'residual_tv' => 3,
            'residual_lh' => 2,
            'residual_rating' => 6,
            'measurement' => 'Accepted',
            'proposed_control' => 'Implement 24/7 SOC with SIEM',
            'lifecycle_status' => 'monitoring',
            'is_enterprise_risk' => true,
        ]);

        $operational = RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'OPS-001',
            'asset_process_service' => 'Printer Cartridge Theft',
            'risk_owner' => 'Office Manager',
            'risk_calculation_date' => '2026-06-15',
            'asset_value_bdt' => 500,
            'threat_level_t' => 2,
            'vulnerability_level_av' => 2,
            'likelihood_lh' => 3,
            'risk_rating_avtvlh' => 12,
            'residual_tv' => 1,
            'residual_lh' => 1,
            'residual_rating' => 1,
            'measurement' => 'Accepted',
            'lifecycle_status' => 'assessed',
            'is_enterprise_risk' => false,
        ]);

        $response = $this->get(route('risk-register.enterprise', $this->project));
        $response->assertStatus(200);
        $response->assertSee('ENT-001');
        $response->assertSee('Board-Level Data Breach Risk');
        $response->assertDontSee('OPS-001');
        $response->assertDontSee('Printer Cartridge Theft');
    }

    public function test_enterprise_risks_show_color_coded_inherent_risk_level()
    {
        $this->actingAs($this->user);

        RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'ENT-002',
            'asset_process_service' => 'Critical Supply Chain Risk',
            'risk_owner' => 'VP Operations',
            'risk_calculation_date' => '2026-06-10',
            'asset_value_bdt' => 2000000,
            'threat_level_t' => 5,
            'vulnerability_level_av' => 5,
            'likelihood_lh' => 5,
            'risk_rating_avtvlh' => 125,
            'residual_tv' => 3,
            'residual_lh' => 3,
            'residual_rating' => 9,
            'measurement' => 'Accepted',
            'lifecycle_status' => 'assessed',
            'is_enterprise_risk' => true,
        ]);

        $response = $this->get(route('risk-register.enterprise', $this->project));

        $response->assertStatus(200);
        $response->assertSee('High');
        $response->assertSee('#c0392b', false);
    }

    public function test_enterprise_risks_kpis_show_correct_counts()
    {
        $this->actingAs($this->user);

        RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'ENT-003',
            'asset_process_service' => 'Regulatory Compliance Risk',
            'risk_owner' => 'Chief Compliance Officer',
            'risk_calculation_date' => '2026-05-01',
            'asset_value_bdt' => 3000000,
            'threat_level_t' => 4,
            'vulnerability_level_av' => 4,
            'likelihood_lh' => 4,
            'risk_rating_avtvlh' => 64,
            'residual_tv' => 2,
            'residual_lh' => 2,
            'residual_rating' => 4,
            'measurement' => 'In Review',
            'lifecycle_status' => 'draft',
            'is_enterprise_risk' => true,
        ]);

        $response = $this->get(route('risk-register.enterprise', $this->project));

        $response->assertStatus(200);
        $response->assertSee('1');
        $response->assertSee('Medium');
    }

    public function test_existing_risk_register_still_shows_all_risks()
    {
        $this->actingAs($this->user);

        Department::create(['name' => 'IT Security']);

        RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'ENT-004',
            'asset_process_service' => 'Enterprise Risk A',
            'risk_owner' => 'CEO',
            'risk_calculation_date' => '2026-06-01',
            'asset_value_bdt' => 1000000,
            'threat_level_t' => 4,
            'vulnerability_level_av' => 4,
            'likelihood_lh' => 4,
            'risk_rating_avtvlh' => 64,
            'residual_tv' => 2,
            'residual_lh' => 2,
            'residual_rating' => 4,
            'measurement' => 'Accepted',
            'lifecycle_status' => 'assessed',
            'is_enterprise_risk' => true,
        ]);

        RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'OPS-002',
            'asset_process_service' => 'Operational Risk B',
            'risk_owner' => 'Team Lead',
            'risk_calculation_date' => '2026-06-15',
            'asset_value_bdt' => 50000,
            'threat_level_t' => 2,
            'vulnerability_level_av' => 2,
            'likelihood_lh' => 2,
            'risk_rating_avtvlh' => 8,
            'residual_tv' => 1,
            'residual_lh' => 1,
            'residual_rating' => 1,
            'measurement' => 'Accepted',
            'lifecycle_status' => 'assessed',
            'is_enterprise_risk' => false,
        ]);

        $response = $this->get(route('risk-register.index', $this->project));
        $response->assertStatus(200);
        $response->assertSee('Enterprise Risk A');
        $response->assertSee('Operational Risk B');
    }

    public function test_enterprise_risk_shows_lifecycle_status_badge()
    {
        $this->actingAs($this->user);

        RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'ENT-005',
            'asset_process_service' => 'Closed Enterprise Risk',
            'risk_owner' => 'CRO',
            'risk_calculation_date' => '2026-04-01',
            'asset_value_bdt' => 1000000,
            'threat_level_t' => 3,
            'vulnerability_level_av' => 3,
            'likelihood_lh' => 3,
            'risk_rating_avtvlh' => 27,
            'residual_tv' => 1,
            'residual_lh' => 1,
            'residual_rating' => 1,
            'measurement' => 'Accepted',
            'lifecycle_status' => 'closed',
            'is_enterprise_risk' => true,
        ]);

        $response = $this->get(route('risk-register.enterprise', $this->project));
        $response->assertStatus(200);
        $response->assertSee('Closed');
    }

    public function test_enterprise_risks_empty_state_shows_message()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('risk-register.enterprise', $this->project));
        $response->assertStatus(200);
        $response->assertSee('No enterprise risks found');
    }

    public function test_enterprise_risks_show_treatment_plan_and_status()
    {
        $this->actingAs($this->user);

        RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'ENT-006',
            'asset_process_service' => 'Treatable Enterprise Risk',
            'risk_owner' => 'CTO',
            'risk_calculation_date' => '2026-06-20',
            'asset_value_bdt' => 5000000,
            'threat_level_t' => 5,
            'vulnerability_level_av' => 5,
            'likelihood_lh' => 5,
            'risk_rating_avtvlh' => 125,
            'residual_tv' => 2,
            'residual_lh' => 2,
            'residual_rating' => 4,
            'measurement' => 'Not Accepted',
            'proposed_control' => 'Deploy automated access controls',
            'lifecycle_status' => 'monitoring',
            'is_enterprise_risk' => true,
        ]);

        $response = $this->get(route('risk-register.enterprise', $this->project));
        $response->assertStatus(200);
        $response->assertSee('Deploy automated access controls');
        $response->assertSee('Not Accepted');
    }
}
