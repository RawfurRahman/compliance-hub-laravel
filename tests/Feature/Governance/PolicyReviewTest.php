<?php

namespace Tests\Feature\Governance;

use App\Models\Role;
use App\Models\User;
use App\Modules\Governance\Models\Domain;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $reviewer;
    protected Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Admin']);

        $this->user = User::factory()->create();
        $this->user->roles()->attach($role->id);

        $this->reviewer = User::factory()->create();
        $this->domain = Domain::factory()->create();
    }

    public function test_can_submit_review_for_policy_under_review(): void
    {
        $this->actingAs($this->user);

        $policy = Policy::factory()->underReview()->create([
            'domain_id' => $this->domain->id,
            'owner_user_id' => $this->user->id,
            'current_version' => 1,
        ]);

        $version = $policy->versions()->create([
            'version_number' => 1,
            'title' => $policy->title,
            'content' => 'Content...',
            'status' => 'under_review',
        ]);

        $response = $this->postJson(
            route('governance-api.reviews.store', $policy),
            [
                'reviewer_user_id' => $this->reviewer->id,
                'review_type' => 'scheduled',
                'due_date' => now()->addDays(7)->toDateString(),
                'policy_version_id' => $version->id,
            ]
        );

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'pending');
        $response->assertJsonPath('data.reviewer.id', $this->reviewer->id);

        $this->assertDatabaseHas('policy_reviews', [
            'policy_id' => $policy->id,
            'reviewer_user_id' => $this->reviewer->id,
            'status' => 'pending',
        ]);
    }

    public function test_can_complete_review(): void
    {
        $this->actingAs($this->user);

        $policy = Policy::factory()->underReview()->create([
            'domain_id' => $this->domain->id,
            'owner_user_id' => $this->user->id,
        ]);

        $review = PolicyReview::create([
            'policy_id' => $policy->id,
            'reviewer_user_id' => $this->reviewer->id,
            'status' => 'pending',
            'review_type' => 'scheduled',
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->putJson(
            route('governance-api.reviews.update', [$policy, $review]),
            [
                'status' => 'completed',
                'comments' => 'All requirements met.',
            ]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('policy_reviews', [
            'id' => $review->id,
            'status' => 'completed',
        ]);
    }

    public function test_review_creates_activity_log_entry(): void
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

        $this->postJson(
            route('governance-api.reviews.store', $policy),
            [
                'reviewer_user_id' => $this->reviewer->id,
                'review_type' => 'scheduled',
            ]
        );

        $this->assertDatabaseHas('activity_log', [
            'action' => 'review_submitted',
        ]);
    }

    public function test_cannot_submit_review_for_draft_policy(): void
    {
        $this->actingAs($this->user);

        $policy = Policy::factory()->draft()->create([
            'domain_id' => $this->domain->id,
            'owner_user_id' => $this->user->id,
        ]);

        $response = $this->postJson(
            route('governance-api.reviews.store', $policy),
            [
                'reviewer_user_id' => $this->reviewer->id,
                'review_type' => 'scheduled',
            ]
        );

        $response->assertStatus(500);
    }
}
