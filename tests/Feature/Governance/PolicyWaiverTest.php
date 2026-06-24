<?php

namespace Tests\Feature\Governance;

use App\Models\Role;
use App\Models\User;
use App\Modules\Governance\Models\Domain;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyWaiver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyWaiverTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $approver;
    protected Policy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Admin']);

        $this->user = User::factory()->create();
        $this->user->roles()->attach($role->id);

        $this->approver = User::factory()->create();
        $this->approver->roles()->attach($role->id);

        $this->policy = Policy::factory()->published()->create([
            'domain_id' => Domain::factory()->create()->id,
            'owner_user_id' => $this->user->id,
        ]);
    }

    public function test_can_request_waiver(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('governance-api.waivers.store'), [
            'policy_id' => $this->policy->id,
            'title' => 'MFA Compliance Waiver',
            'description' => 'Legacy system cannot support MFA.',
            'justification' => 'System being decommissioned in Q3.',
            'effective_date' => now()->toDateString(),
            'expires_at' => now()->addMonths(3)->toDateString(),
            'department' => 'IT',
            'compensating_controls' => 'Network segmentation and IP whitelisting.',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('policy_waivers', [
            'policy_id' => $this->policy->id,
            'title' => 'MFA Compliance Waiver',
            'status' => 'pending',
        ]);
    }

    public function test_can_approve_pending_waiver(): void
    {
        $this->actingAs($this->user);

        $waiver = PolicyWaiver::create([
            'policy_id' => $this->policy->id,
            'title' => 'Test Waiver',
            'description' => 'Description.',
            'justification' => 'Justification.',
            'requested_by' => $this->user->id,
            'status' => 'pending',
            'effective_date' => now()->toDateString(),
            'expires_at' => now()->addMonths(3)->toDateString(),
        ]);

        $response = $this->putJson(
            route('governance-api.waivers.approve', $waiver),
            ['approved_by' => $this->approver->id]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('policy_waivers', [
            'id' => $waiver->id,
            'status' => 'approved',
            'approved_by' => $this->approver->id,
        ]);
    }

    public function test_can_reject_pending_waiver(): void
    {
        $this->actingAs($this->user);

        $waiver = PolicyWaiver::create([
            'policy_id' => $this->policy->id,
            'title' => 'Test Waiver',
            'description' => 'Description.',
            'justification' => 'Justification.',
            'requested_by' => $this->user->id,
            'status' => 'pending',
            'effective_date' => now()->toDateString(),
            'expires_at' => now()->addMonths(3)->toDateString(),
        ]);

        $response = $this->putJson(
            route('governance-api.waivers.reject', $waiver),
            ['reason' => 'Insufficient compensating controls.']
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('policy_waivers', [
            'id' => $waiver->id,
            'status' => 'rejected',
        ]);
    }

    public function test_waiver_creates_activity_log_entry(): void
    {
        $this->actingAs($this->user);

        $this->postJson(route('governance-api.waivers.store'), [
            'policy_id' => $this->policy->id,
            'title' => 'Logging Waiver',
            'description' => 'Description.',
            'justification' => 'Justification.',
            'effective_date' => now()->toDateString(),
            'expires_at' => now()->addMonths(3)->toDateString(),
        ]);

        $this->assertDatabaseHas('activity_log', [
            'action' => 'waiver_requested',
        ]);
    }

    public function test_can_list_waivers_for_policy(): void
    {
        $this->actingAs($this->user);

        PolicyWaiver::create([
            'policy_id' => $this->policy->id,
            'title' => 'Waiver 1',
            'description' => 'Desc 1',
            'justification' => 'Just 1',
            'requested_by' => $this->user->id,
            'status' => 'approved',
            'effective_date' => now()->toDateString(),
            'expires_at' => now()->addMonths(3)->toDateString(),
        ]);

        PolicyWaiver::create([
            'policy_id' => $this->policy->id,
            'title' => 'Waiver 2',
            'description' => 'Desc 2',
            'justification' => 'Just 2',
            'requested_by' => $this->user->id,
            'status' => 'pending',
            'effective_date' => now()->toDateString(),
            'expires_at' => now()->addMonths(3)->toDateString(),
        ]);

        $response = $this->getJson(
            route('governance-api.waivers.index', $this->policy)
        );

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }
}
