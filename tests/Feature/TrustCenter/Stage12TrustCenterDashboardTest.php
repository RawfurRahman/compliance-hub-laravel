<?php

namespace Tests\Feature\TrustCenter;

use App\Models\Project;
use App\Models\User;
use App\Modules\TrustCenter\Models\TrustCenter;
use App\Modules\TrustCenter\Models\TrustCenterAccessRequest;
use App\Modules\TrustCenter\Models\TrustCenterQuestionnaire;
use App\Modules\TrustCenter\Models\TrustCenterVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Stage12TrustCenterDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected TrustCenter $trustCenter;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('trust_center_visits');
        Schema::dropIfExists('trust_center_questionnaires');
        Schema::dropIfExists('trust_center_access_requests');
        Schema::dropIfExists('evidence_files');
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
        Schema::create('evidence_files', function ($table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->foreignId('trust_center_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_publicly_listed')->default(false);
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
        Schema::create('trust_center_questionnaires', function ($table) {
            $table->id();
            $table->foreignId('trust_center_id')->constrained()->cascadeOnDelete();
            $table->string('requester_name');
            $table->string('requester_email');
            $table->string('requester_company')->nullable();
            $table->string('status')->default('Submitted');
            $table->text('responses');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
        Schema::create('trust_center_visits', function ($table) {
            $table->id();
            $table->foreignId('trust_center_id')->constrained()->cascadeOnDelete();
            $table->timestamp('visited_at');
            $table->string('ip_hash', 64);
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

    public function test_visit_is_logged_on_public_page_load(): void
    {
        $this->get(route('trust-center.public.show', $this->trustCenter->public_slug));

        $this->assertDatabaseHas('trust_center_visits', [
            'trust_center_id' => $this->trustCenter->id,
        ]);

        $visit = TrustCenterVisit::where('trust_center_id', $this->trustCenter->id)->first();
        $this->assertNotNull($visit->visited_at);
        $this->assertEquals(64, strlen($visit->ip_hash));
    }

    public function test_multiple_visits_are_counted(): void
    {
        $this->get(route('trust-center.public.show', $this->trustCenter->public_slug));
        $this->get(route('trust-center.public.show', $this->trustCenter->public_slug));
        $this->get(route('trust-center.public.show', $this->trustCenter->public_slug));

        $response = $this->actingAs($this->user)
            ->get(route('admin.trust-centers.overview', $this->trustCenter));

        $response->assertStatus(200);
        $response->assertSee('3');
    }

    public function test_dashboard_shows_pending_request_count(): void
    {
        TrustCenterAccessRequest::create([
            'trust_center_id' => $this->trustCenter->id,
            'requester_name'  => 'Jane',
            'requester_email' => 'jane@test.com',
            'status'          => 'Pending',
        ]);
        TrustCenterAccessRequest::create([
            'trust_center_id' => $this->trustCenter->id,
            'requester_name'  => 'John',
            'requester_email' => 'john@test.com',
            'status'          => 'Pending',
        ]);
        TrustCenterAccessRequest::create([
            'trust_center_id' => $this->trustCenter->id,
            'requester_name'  => 'Done',
            'requester_email' => 'done@test.com',
            'status'          => 'Approved',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.trust-centers.overview', $this->trustCenter));

        $response->assertStatus(200);
        $response->assertSee('2');
    }

    public function test_dashboard_shows_unresponded_questionnaire_count(): void
    {
        TrustCenterQuestionnaire::create([
            'trust_center_id' => $this->trustCenter->id,
            'requester_name'  => 'Jane',
            'requester_email' => 'jane@test.com',
            'status'          => 'Submitted',
            'responses'       => [],
            'submitted_at'    => now(),
        ]);
        TrustCenterQuestionnaire::create([
            'trust_center_id' => $this->trustCenter->id,
            'requester_name'  => 'John',
            'requester_email' => 'john@test.com',
            'status'          => 'In Review',
            'responses'       => [],
            'submitted_at'    => now(),
        ]);
        TrustCenterQuestionnaire::create([
            'trust_center_id' => $this->trustCenter->id,
            'requester_name'  => 'Done',
            'requester_email' => 'done@test.com',
            'status'          => 'Responded',
            'responses'       => [],
            'submitted_at'    => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.trust-centers.overview', $this->trustCenter));

        $response->assertStatus(200);
        $response->assertSee('2');
    }

    public function test_dashboard_date_range_filters_visits(): void
    {
        TrustCenterVisit::create([
            'trust_center_id' => $this->trustCenter->id,
            'visited_at'      => now()->subDays(5),
            'ip_hash'         => 'a',
        ]);
        TrustCenterVisit::create([
            'trust_center_id' => $this->trustCenter->id,
            'visited_at'      => now()->subDays(5),
            'ip_hash'         => 'b',
        ]);
        TrustCenterVisit::create([
            'trust_center_id' => $this->trustCenter->id,
            'visited_at'      => now()->subDays(60),
            'ip_hash'         => 'c',
        ]);

        $from = now()->subDays(10)->format('Y-m-d');
        $to = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->get(route('admin.trust-centers.overview', [$this->trustCenter, 'from' => $from, 'to' => $to]));

        $response->assertStatus(200);
        $response->assertSee('2');

        $oldFrom = now()->subDays(90)->format('Y-m-d');
        $oldTo = now()->subDays(30)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->get(route('admin.trust-centers.overview', [$this->trustCenter, 'from' => $oldFrom, 'to' => $oldTo]));

        $response->assertStatus(200);
        $response->assertSee('1');
    }
}
