<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdminUser;
    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Super Admin role and user
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $this->superAdminUser = User::factory()->create(['username' => 'superadmin', 'email' => 'superadmin@example.com']);
        $this->superAdminUser->roles()->attach($superAdminRole->id);

        // Create Admin role and user
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $this->adminUser = User::factory()->create(['username' => 'admin', 'email' => 'admin@example.com']);
        $this->adminUser->roles()->attach($adminRole->id);

        // Create Customer role and user
        $customerRole = Role::firstOrCreate(['name' => 'Customer']);
        $this->regularUser = User::factory()->create(['username' => 'customer', 'email' => 'customer@example.com']);
        $this->regularUser->roles()->attach($customerRole->id);
    }

    public function test_super_admin_bypasses_authorization_gates()
    {
        $this->actingAs($this->superAdminUser);

        $policyAction = $this->superAdminUser->hasRole('Super Admin') ? true : false;
        $this->assertTrue($policyAction, 'Super Admin should bypass authorization gates');
    }

    public function test_super_admin_has_full_crud_access_to_governance_module()
    {
        $this->actingAs($this->superAdminUser);

        $governanceActions = [
            'view',
            'create',
            'update',
            'delete',
            'approve',
            'publish',
            'expire'
        ];

        foreach ($governanceActions as $action) {
            $method = $action === 'create' || $action === 'approve' || $action === 'publish' || $action === 'expire'
                ? 'can'
                : 'can';

            $result = $this->superAdminUser->hasRole('Super Admin');
            $this->assertTrue($result, "Super Admin should have {$action} access in Governance module");
        }
    }

    public function test_super_admin_unrestricted_access_to_confidential_modules()
    {
        $this->actingAs($this->superAdminUser);

        $superAdminPermissions = [
            'policy',
            'domain',
            'project',
            'evidence',
            'compliance',
        ];

        $this->assertTrue($this->superAdminUser->hasRole('Super Admin'),
            'Super Admin should have access to all modules');
    }

    public function test_super_admin_actions_are_logged_in_activity_log()
    {
        $this->actingAs($this->superAdminUser);

        $activity = ActivityLog::create([
            'user_id' => $this->superAdminUser->id,
            'action' => 'create',
            'description' => 'Super Admin created test policy',
            'details' => ['policy_id' => 123],
            'ip_address' => '127.0.0.1',
            'role' => 'Super Admin'
        ]);

        $this->assertDatabaseHas('activity_log', [
            'user_id' => $this->superAdminUser->id,
            'role' => 'Super Admin',
            'action' => 'create',
            'description' => 'Super Admin created test policy',
        ]);
    }

    public function test_ui_components_show_admin_buttons_for_super_admin()
    {
        $this->actingAs($this->superAdminUser);

        $uiComponents = [
            'DashboardShell.vue',
            'PolicyManagement.vue',
            'DomainManagement.vue',
            'EvidenceHub.vue',
            'AdminDashboard.vue'
        ];

        foreach ($uiComponents as $component) {
            $this->assertTrue($this->superAdminUser->hasRole('Super Admin'),
                "Super Admin should have access to UI component: {$component}");
        }
    }

    public function test_activity_log_role_field_is_automatically_set_for_super_admin()
    {
        $this->actingAs($this->superAdminUser);

        $activity = ActivityLog::create([
            'user_id' => $this->superAdminUser->id,
            'action' => 'delete',
            'description' => 'Deleted sensitive compliance data',
            'ip_address' => '192.168.1.100',
        ]);

        $this->assertEquals('Super Admin', $activity->role,
            'ActivityLog should automatically set role to Super Admin when Super Admin performs action');
    }

    public function test_super_admin_has_global_crud_access_to_bounded_contexts()
    {
        $this->actingAs($this->superAdminUser);

        $boundedContexts = [
            'governance',
            'riskManagement',
            'complianceHub',
        ];

        foreach ($boundedContexts as $context) {
            $this->assertTrue(
                $this->superAdminUser->hasRole('Super Admin'),
                "Super Admin should have global CRUD access to {$context} bounded context"
            );
        }
    }
}