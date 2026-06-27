<?php

namespace Tests\Feature\TrustCenter;

use App\Models\Project;
use App\Models\User;
use App\Modules\TrustCenter\Models\TrustCenter;
use App\Modules\TrustCenter\Models\TrustCenterQuestionnaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Stage11TrustCenterQuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected TrustCenter $trustCenter;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_questionnaire_submission_creates_record(): void
    {
        $response = $this->post(
            route('trust-center.public.questionnaire', $this->trustCenter->public_slug),
            [
                'name'    => 'Jane Requester',
                'email'   => 'jane@example.com',
                'company' => 'Acme Corp',
                'responses' => [
                    'security_certifications' => 'ISO 27001, SOC 2 Type II',
                    'data_encryption'         => 'Yes, AES-256 at rest and TLS 1.3 in transit',
                    'incident_response'       => 'We follow NIST framework',
                ],
            ]
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('trust_center_questionnaires', [
            'trust_center_id'   => $this->trustCenter->id,
            'requester_name'    => 'Jane Requester',
            'requester_email'   => 'jane@example.com',
            'requester_company' => 'Acme Corp',
            'status'            => 'Submitted',
        ]);

        $record = TrustCenterQuestionnaire::where('requester_email', 'jane@example.com')->first();

        $this->assertNotNull($record->submitted_at);

        $this->assertIsArray($record->responses);
        $responseKeys = collect($record->responses)->pluck('key')->toArray();
        $this->assertContains('security_certifications', $responseKeys);
        $this->assertContains('data_encryption', $responseKeys);
        $this->assertContains('incident_response', $responseKeys);

        $matched = collect($record->responses)->firstWhere('key', 'security_certifications');
        $this->assertEquals('ISO 27001, SOC 2 Type II', $matched['answer']);
    }

    public function test_questionnaire_validates_required_fields(): void
    {
        $response = $this->post(
            route('trust-center.public.questionnaire', $this->trustCenter->public_slug),
            [
                'name'      => '',
                'email'     => 'jane@example.com',
                'responses' => [],
            ]
        );

        $response->assertSessionHasErrors('name');
    }

    public function test_questionnaire_validates_email_format(): void
    {
        $response = $this->post(
            route('trust-center.public.questionnaire', $this->trustCenter->public_slug),
            [
                'name'      => 'Jane Requester',
                'email'     => 'not-an-email',
                'responses' => [
                    'security_certifications' => 'ISO 27001',
                ],
            ]
        );

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_view_questionnaires_list(): void
    {
        TrustCenterQuestionnaire::create([
            'trust_center_id'  => $this->trustCenter->id,
            'requester_name'   => 'Jane Requester',
            'requester_email'  => 'jane@example.com',
            'requester_company' => 'Acme Corp',
            'status'           => 'Submitted',
            'responses'        => [
                ['key' => 'security_certifications', 'question' => 'What security certifications?', 'answer' => 'ISO 27001'],
            ],
            'submitted_at'     => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.trust-centers.questionnaires', $this->trustCenter));

        $response->assertStatus(200);
        $response->assertSee('Jane Requester');
        $response->assertSee('jane@example.com');
        $response->assertSee('Acme Corp');
        $response->assertSee('Submitted');
    }

    public function test_admin_can_mark_questionnaire_responded(): void
    {
        $questionnaire = TrustCenterQuestionnaire::create([
            'trust_center_id'  => $this->trustCenter->id,
            'requester_name'   => 'Jane Requester',
            'requester_email'  => 'jane@example.com',
            'status'           => 'Submitted',
            'responses'        => [],
            'submitted_at'     => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('admin.trust-centers.questionnaires.responded', [$this->trustCenter, $questionnaire]));

        $response->assertSessionHas('success');

        $fresh = $questionnaire->fresh();
        $this->assertEquals('Responded', $fresh->status);
        $this->assertNotNull($fresh->responded_at);
    }

    public function test_end_to_end_submission_and_admin_view(): void
    {
        $this->post(
            route('trust-center.public.questionnaire', $this->trustCenter->public_slug),
            [
                'name'    => 'Jane Requester',
                'email'   => 'jane@example.com',
                'company' => 'Acme Corp',
                'responses' => [
                    'security_certifications' => 'ISO 27001, SOC 2 Type II',
                    'data_encryption'         => 'AES-256 and TLS 1.3',
                    'incident_response'       => 'NIST framework',
                    'access_controls'         => 'RBAC with MFA',
                    'business_continuity'     => 'DR plan tested quarterly',
                    'data_retention'          => '7 years',
                    'penetration_testing'     => 'Annually',
                    'subprocessors'           => 'AWS, Cloudflare',
                    'data_hosting'            => 'US East (N. Virginia)',
                    'compliance_frameworks'   => 'ISO 27001, SOC 2, GDPR',
                ],
            ]
        );

        $response = $this->actingAs($this->user)
            ->get(route('admin.trust-centers.questionnaires', $this->trustCenter));

        $response->assertStatus(200);
        $response->assertSee('Jane Requester');
        $response->assertSee('jane@example.com');
        $response->assertSee('Acme Corp');
        $response->assertSee('Submitted');

        $response->assertSee('ISO 27001, SOC 2 Type II');
        $response->assertSee('AES-256 and TLS 1.3');
        $response->assertSee('RBAC with MFA');
        $response->assertSee('US East (N. Virginia)');
    }
}
