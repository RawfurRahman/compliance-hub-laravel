<?php

namespace Tests\Feature\Compliance;

use App\Models\Control;
use App\Models\User;
use App\Modules\Compliance\Models\ComplianceTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Stage6ControlTestMappingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Control $control;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
        Schema::create('user_roles', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
        });

        $adminRoleId = DB::table('roles')->insertGetId(['name' => 'Admin']);

        $this->user = User::factory()->create([
            'username' => 'testadmin',
            'email' => 'admin@test.com',
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $this->user->id,
            'role_id' => $adminRoleId,
        ]);

        $this->control = Control::create([
            'control_code' => 'NET-4',
            'code' => 'NET-4',
            'name' => 'Network Access Control',
            'title' => 'Network Access Control',
            'description' => 'Ensures proper network segmentation and access controls.',
            'is_active' => true,
            'status' => 'active',
            'effectiveness_score' => 75,
        ]);
    }

    private function createTest(string $name, string $status, string $testType = 'Automated'): ComplianceTest
    {
        return ComplianceTest::create([
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
            'integration_id' => null,
            'control_id' => $this->control->id,
        ]);
    }

    public function test_control_edit_page_shows_mapped_tests_section(): void
    {
        $this->createTest('Firewall Rule Review', 'Passing', 'Automated');
        $this->createTest('Segmentation Check', 'Passing', 'Automated');
        $this->createTest('Access Log Audit', 'Overdue', 'Manual');

        $response = $this->actingAs($this->user)
            ->get(route('admin.controls.edit', $this->control));

        $response->assertStatus(200);
        $response->assertSee('Mapped Compliance Tests');
        $response->assertSee('Firewall Rule Review');
        $response->assertSee('Segmentation Check');
        $response->assertSee('Access Log Audit');
    }

    public function test_passing_count_is_correct(): void
    {
        $this->createTest('Test A', 'Passing');
        $this->createTest('Test B', 'Passing');

        $response = $this->actingAs($this->user)
            ->get(route('admin.controls.edit', $this->control));

        $response->assertStatus(200);
        $response->assertSee('Test A');
        $response->assertSee('Test B');
    }

    public function test_no_tests_shows_empty_state(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.controls.edit', $this->control));

        $response->assertStatus(200);
        $response->assertSee('No compliance tests mapped to this control yet.');
    }

    public function test_mixed_statuses_are_displayed(): void
    {
        $this->createTest('Overdue Test', 'Overdue');
        $this->createTest('Failed Test', 'Needs Remediation');

        $response = $this->actingAs($this->user)
            ->get(route('admin.controls.edit', $this->control));

        $response->assertStatus(200);
        $response->assertSee('Overdue Test');
        $response->assertSee('Failed Test');
        $response->assertSee('Overdue');
        $response->assertSee('Needs Remediation');
    }

    public function test_all_status_badges_render(): void
    {
        $this->createTest('Passing Test', 'Passing');
        $this->createTest('Overdue Test', 'Overdue');
        $this->createTest('Needs Remediation Test', 'Needs Remediation');
        $this->createTest('Due Soon Test', 'Due Soon');
        $this->createTest('Not Yet Run Test', 'Not Yet Run');

        $response = $this->actingAs($this->user)
            ->get(route('admin.controls.edit', $this->control));

        $response->assertStatus(200);
        $response->assertSee('Passing');
        $response->assertSee('Overdue');
        $response->assertSee('Needs Remediation');
        $response->assertSee('Due Soon');
        $response->assertSee('Not Yet Run');
    }

    public function test_pass_rate_is_accurate(): void
    {
        $this->createTest('P1', 'Passing');
        $this->createTest('P2', 'Passing');
        $this->createTest('O1', 'Overdue');

        $response = $this->actingAs($this->user)
            ->get(route('admin.controls.edit', $this->control));

        $response->assertStatus(200);
        $response->assertSee('Mapped Compliance Tests');

        // Fetch the control back and verify the counts through the model relationship
        $control = Control::with('complianceTests')->find($this->control->id);
        $this->assertEquals(3, $control->complianceTests->count());
        $this->assertEquals(2, $control->complianceTests->where('status', 'Passing')->count());
    }
}
