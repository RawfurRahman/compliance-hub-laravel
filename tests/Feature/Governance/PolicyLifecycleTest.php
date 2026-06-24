<?php

namespace Tests\Feature\Governance;

use App\Models\Role;
use App\Models\User;
use App\Modules\Governance\Models\Domain;
use App\Modules\Governance\Models\Policy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Admin']);

        $this->user = User::factory()->create();
        $this->user->roles()->attach($role->id);

        $this->domain = Domain::factory()->create();
    }

    public function test_can_create_policy_as_draft(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('governance-api.policies.store'), [
            'domain_id' => $this->domain->id,
            'title' => 'Acceptable Use Policy',
            'description' => 'Governs acceptable use of company IT resources.',
            'content' => '## Policy Content\n\nAll employees must use company resources responsibly.',
            'owner_user_id' => $this->user->id,
            'department' => 'IT',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('policies', [
            'title' => 'Acceptable Use Policy',
            'status' => 'draft',
        ]);
    }

    public function test_can_submit_draft_for_review(): void
    {
        $this->actingAs($this->user);

        $policy = Policy::factory()->draft()->create([
            'domain_id' => $this->domain->id,
            'owner_user_id' => $this->user->id,
        ]);

        $policy->versions()->create([
            'version_number' => 1,
            'title' => $policy->title,
            'content' => 'Policy content here...',
            'status' => 'draft',
        ]);
        $policy->update(['current_version' => 1]);

        $response = $this->postJson(
            route('governance-api.policies.submit-review', $policy),
            ['comment' => 'Ready for review.']
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'under_review');

        $this->assertDatabaseHas('policies', [
            'id' => $policy->id,
            'status' => 'under_review',
        ]);
    }

    public function test_cannot_submit_draft_without_version(): void
    {
        $this->actingAs($this->user);

        $policy = Policy::factory()->draft()->create([
            'domain_id' => $this->domain->id,
            'owner_user_id' => $this->user->id,
        ]);

        $response = $this->postJson(
            route('governance-api.policies.submit-review', $policy),
            ['comment' => 'Ready for review.']
        );

        $response->assertStatus(500);
    }

    public function test_can_approve_under_review_policy(): void
    {
        $this->actingAs($this->user);

        $policy = Policy::factory()->underReview()->create([
            'domain_id' => $this->domain->id,
            'owner_user_id' => $this->user->id,
            'current_version' => 1,
        ]);

        $policy->versions()->create([
            'version_number' => 1,
            'title' => $policy->title,
            'content' => 'Content...',
            'status' => 'under_review',
        ]);

        $response = $this->postJson(
            route('governance-api.approvals.store', $policy),
            ['approver_user_id' => $this->user->id]
        );

        $response->assertStatus(201);

        $approvalId = $response->json('data.id');

        $approveResponse = $this->putJson(
            route('governance-api.approvals.approve', [$policy, $approvalId]),
            ['comments' => 'Looks good.']
        );

        $approveResponse->assertStatus(200);
        $approveResponse->assertJsonPath('message', 'Policy approved.');
    }

    public function test_can_publish_approved_policy_with_effective_date(): void
    {
        $this->actingAs($this->user);

        $policy = Policy::factory()->approved()->create([
            'domain_id' => $this->domain->id,
            'owner_user_id' => $this->user->id,
            'current_version' => 1,
        ]);

        $policy->versions()->create([
            'version_number' => 1,
            'title' => $policy->title,
            'content' => 'Content...',
            'status' => 'approved',
        ]);

        $effectiveDate = now()->toDateString();

        $response = $this->postJson(
            route('governance-api.policies.publish', $policy),
            ['effective_date' => $effectiveDate]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'published');
        $this->assertStringContainsString($effectiveDate, $response->json('data.effective_date'));

        $this->assertDatabaseHas('policies', [
            'id' => $policy->id,
            'status' => 'published',
        ]);
    }

    public function test_cannot_publish_without_effective_date(): void
    {
        $this->actingAs($this->user);

        $policy = Policy::factory()->approved()->create([
            'domain_id' => $this->domain->id,
            'owner_user_id' => $this->user->id,
            'current_version' => 1,
        ]);

        $policy->versions()->create([
            'version_number' => 1,
            'title' => $policy->title,
            'content' => 'Content...',
            'status' => 'approved',
        ]);

        $response = $this->postJson(
            route('governance-api.policies.publish', $policy),
            ['effective_date' => '']
        );

        $response->assertStatus(422);
    }

    public function test_can_deprecate_published_policy(): void
    {
        $this->actingAs($this->user);

        $policy = Policy::factory()->published()->create([
            'domain_id' => $this->domain->id,
            'owner_user_id' => $this->user->id,
        ]);

        $response = $this->postJson(
            route('governance-api.policies.deprecate', $policy),
            ['reason' => 'Superseded by v2.']
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'deprecated');
    }

    public function test_can_archive_deprecated_policy(): void
    {
        $this->actingAs($this->user);

        $policy = Policy::factory()->deprecated()->create([
            'domain_id' => $this->domain->id,
            'owner_user_id' => $this->user->id,
        ]);

        $response = $this->postJson(
            route('governance-api.policies.archive', $policy)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'archived');
    }

    public function test_can_reactivate_archived_policy(): void
    {
        $this->actingAs($this->user);

        $policy = Policy::factory()->archived()->create([
            'domain_id' => $this->domain->id,
            'owner_user_id' => $this->user->id,
        ]);

        $response = $this->postJson(
            route('governance-api.policies.reactivate', $policy)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'draft');
    }

    public function test_cannot_transition_from_draft_to_published_directly(): void
    {
        $this->actingAs($this->user);

        $policy = Policy::factory()->draft()->create([
            'domain_id' => $this->domain->id,
            'owner_user_id' => $this->user->id,
        ]);

        $response = $this->postJson(
            route('governance-api.policies.publish', $policy),
            ['effective_date' => now()->toDateString()]
        );

        $response->assertStatus(500);
    }

    public function test_policy_lifecycle_creates_activity_log_entries(): void
    {
        $this->actingAs($this->user);

        $policy = Policy::factory()->draft()->create([
            'domain_id' => $this->domain->id,
            'owner_user_id' => $this->user->id,
        ]);

        $policy->versions()->create([
            'version_number' => 1,
            'title' => $policy->title,
            'content' => 'Content...',
            'status' => 'draft',
        ]);
        $policy->update(['current_version' => 1]);

        $this->postJson(
            route('governance-api.policies.submit-review', $policy),
            ['comment' => 'Ready.']
        );

        $this->assertDatabaseHas('activity_log', [
            'action' => 'policy_submitted_for_review',
        ]);
    }

    public function test_can_expire_published_policy(): void
    {
        $this->actingAs($this->user);

        $policy = Policy::factory()->published()->create([
            'domain_id' => $this->domain->id,
            'owner_user_id' => $this->user->id,
        ]);

        $response = $this->postJson(
            route('governance-api.policies.expire', $policy)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'expired');
    }
}
