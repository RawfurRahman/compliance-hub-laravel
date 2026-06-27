<?php

namespace Tests\Feature\TrustCenter;

use App\Mail\TrustCenterAccessGrantedMail;
use App\Models\Framework;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\User;
use App\Modules\TrustCenter\Models\TrustCenter;
use App\Modules\TrustCenter\Models\TrustCenterAccessRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Stage9TrustCenterRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected TrustCenter $trustCenter;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('trust_center_access_requests');
        Schema::dropIfExists('project_assessments');
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
        Schema::create('project_assessments', function ($table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('framework_id')->constrained()->nullOnDelete();
            $table->string('type');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('overall_status')->nullable();
            $table->unsignedBigInteger('cloned_from_id')->nullable();
            $table->boolean('is_publicly_visible')->default(false);
            $table->timestamps();
        });
        Schema::create('trust_center_access_requests', function ($table) {
            $table->id();
            $table->foreignId('trust_center_id')->constrained()->cascadeOnDelete();
            $table->string('requester_name');
            $table->string('requester_email');
            $table->string('requester_company')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('Pending');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
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

        $this->project = Project::create([
            'name' => 'Test Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);

        $this->trustCenter = TrustCenter::create([
            'project_id' => $this->project->id,
            'is_published' => true,
            'headline' => 'Security at Test Company',
            'summary' => 'We take security seriously.',
            'contact_email' => 'security@test.com',
        ]);
    }

    public function test_request_shows_as_pending_after_public_submission(): void
    {
        $response = $this->post(
            route('trust-center.public.request-access', $this->trustCenter->public_slug),
            [
                'name'    => 'Jane Requester',
                'email'   => 'jane@example.com',
                'company' => 'Acme Corp',
                'note'    => 'I would like to review your compliance details.',
            ]
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('trust_center_access_requests', [
            'trust_center_id'   => $this->trustCenter->id,
            'requester_name'    => 'Jane Requester',
            'requester_email'   => 'jane@example.com',
            'requester_company' => 'Acme Corp',
            'note'              => 'I would like to review your compliance details.',
            'status'            => 'Pending',
            'reviewed_by_user_id' => null,
            'reviewed_at'       => null,
        ]);
    }

    public function test_public_form_validates_name_required(): void
    {
        $response = $this->post(
            route('trust-center.public.request-access', $this->trustCenter->public_slug),
            [
                'name'  => '',
                'email' => 'jane@example.com',
            ]
        );

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_view_requests_list(): void
    {
        $accessRequest = TrustCenterAccessRequest::create([
            'trust_center_id'  => $this->trustCenter->id,
            'requester_name'   => 'Jane Requester',
            'requester_email'  => 'jane@example.com',
            'requester_company' => 'Acme Corp',
            'note'             => 'I would like to review your compliance details.',
            'status'           => 'Pending',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.trust-centers.requests', $this->trustCenter));

        $response->assertStatus(200);
        $response->assertSee('Jane Requester');
        $response->assertSee('jane@example.com');
        $response->assertSee('Acme Corp');
        $response->assertSee('Pending');
    }

    public function test_admin_can_approve_request_and_email_is_sent(): void
    {
        Mail::fake();

        $accessRequest = TrustCenterAccessRequest::create([
            'trust_center_id'  => $this->trustCenter->id,
            'requester_name'   => 'Jane Requester',
            'requester_email'  => 'jane@example.com',
            'status'           => 'Pending',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('admin.trust-centers.requests.approve', [$this->trustCenter, $accessRequest]));

        $response->assertSessionHas('success');

        $fresh = $accessRequest->fresh();
        $this->assertEquals('Approved', $fresh->status);
        $this->assertEquals($this->user->id, $fresh->reviewed_by_user_id);
        $this->assertNotNull($fresh->reviewed_at);

        Mail::assertSent(TrustCenterAccessGrantedMail::class, function ($mail) use ($accessRequest) {
            return $mail->hasTo('jane@example.com')
                && $mail->trustCenter->is($this->trustCenter)
                && $mail->accessRequest->is($accessRequest);
        });
    }

    public function test_admin_can_deny_request(): void
    {
        $accessRequest = TrustCenterAccessRequest::create([
            'trust_center_id'  => $this->trustCenter->id,
            'requester_name'   => 'Jane Requester',
            'requester_email'  => 'jane@example.com',
            'status'           => 'Pending',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('admin.trust-centers.requests.deny', [$this->trustCenter, $accessRequest]));

        $response->assertSessionHas('success');

        $fresh = $accessRequest->fresh();
        $this->assertEquals('Denied', $fresh->status);
        $this->assertEquals($this->user->id, $fresh->reviewed_by_user_id);
        $this->assertNotNull($fresh->reviewed_at);
    }

    public function test_denied_request_does_not_send_email(): void
    {
        Mail::fake();

        $accessRequest = TrustCenterAccessRequest::create([
            'trust_center_id'  => $this->trustCenter->id,
            'requester_name'   => 'Jane Requester',
            'requester_email'  => 'jane@example.com',
            'status'           => 'Pending',
        ]);

        $this->actingAs($this->user)
            ->post(route('admin.trust-centers.requests.deny', [$this->trustCenter, $accessRequest]));

        Mail::assertNothingSent();
    }

    public function test_approve_wrong_trust_center_returns_404(): void
    {
        $otherProject = Project::create([
            'name' => 'Other Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);

        $otherTc = TrustCenter::create([
            'project_id' => $otherProject->id,
            'is_published' => true,
            'headline' => 'Other TC',
            'summary' => 'Other summary.',
        ]);

        $accessRequest = TrustCenterAccessRequest::create([
            'trust_center_id'  => $this->trustCenter->id,
            'requester_name'   => 'Jane Requester',
            'requester_email'  => 'jane@example.com',
            'status'           => 'Pending',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('admin.trust-centers.requests.approve', [$otherTc, $accessRequest]));

        $response->assertStatus(404);
    }
}
