<?php

namespace Tests\Feature;

use App\Models\EvidenceFile;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\User;
use App\Services\DirectEvidenceAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Stage14EvidenceGapCheckTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Project $project;
    protected Framework $framework;
    protected FrameworkControl $control;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('framework_controls');
        Schema::dropIfExists('frameworks');
        Schema::dropIfExists('evidence_files');
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
        Schema::create('frameworks', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
        Schema::create('framework_controls', function ($table) {
            $table->id();
            $table->foreignId('framework_id')->constrained()->cascadeOnDelete();
            $table->string('control_id');
            $table->string('control_name')->nullable();
            $table->text('requirement_description')->nullable();
            $table->string('domain')->nullable();
            $table->timestamps();
        });
        Schema::create('evidence_files', function ($table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('framework_control_id')->nullable();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->string('scan_status')->default('pending');
            $table->text('scan_details')->nullable();
            $table->text('ai_observations')->nullable();
            $table->text('ai_recommendations')->nullable();
            $table->text('ai_gaps')->nullable();
            $table->string('ai_analysis_status')->default('pending');
            $table->unsignedBigInteger('ai_analysis_approved_by')->nullable();
            $table->datetime('ai_analysis_approved_at')->nullable();
            $table->string('hitl_status')->default('pending_review');
            $table->text('customer_response')->nullable();
            $table->timestamps();
        });
        Schema::create('evidence_feedbacks', function ($table) {
            $table->id();
            $table->foreignId('evidence_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();
        });

        $adminRoleId = DB::table('roles')->insertGetId(['name' => 'Admin']);

        $this->admin = User::factory()->create([
            'username' => 'testadmin',
            'email' => 'admin@test.com',
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $this->admin->id,
            'role_id' => $adminRoleId,
        ]);

        $this->project = Project::create([
            'name' => 'Test Evidence Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->admin->id,
        ]);

        $this->framework = Framework::create([
            'name' => 'ISO 27001:2022',
            'slug' => 'iso_27001',
        ]);

        $this->control = FrameworkControl::create([
            'framework_id' => $this->framework->id,
            'control_id' => 'A.9.4.2',
            'control_name' => 'Secure Log-on Procedures',
            'requirement_description' => 'Access control policy shall require multi-factor authentication for all remote access to the organization\'s network.',
            'domain' => 'Access Control',
        ]);
    }

    public function test_service_stores_gaps_from_gemini(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"observations":"1. File provides a password policy document.\\n2. No mention of MFA enforcement.","recommendations":"1. Implement MFA for remote access.\\n2. Document MFA configuration.","gaps":[{"gap":"No evidence of multi-factor authentication enforcement","severity":"high"},{"gap":"Screen lock timeout not documented","severity":"medium"}]}']]]]
                ]
            ], 200),
            'localhost:9000/*' => Http::response(['infected' => false], 200),
        ]);

        Storage::fake('public');
        $path = 'evidence/' . $this->project->id . '/access-policy.pdf';
        Storage::disk('public')->put($path, 'fake pdf content for testing');

        $evidence = EvidenceFile::create([
            'project_id' => $this->project->id,
            'user_id' => $this->admin->id,
            'framework_control_id' => $this->control->id,
            'file_path' => $path,
            'original_filename' => 'access-policy.pdf',
            'mime_type' => 'application/pdf',
            'scan_status' => 'pending',
            'ai_analysis_status' => 'pending',
        ]);

        $service = app(DirectEvidenceAnalysisService::class);
        $result = $service->process($evidence);

        $this->assertEquals('awaiting_review', $result->ai_analysis_status);
        $this->assertNotNull($result->ai_gaps);
        $this->assertCount(2, $result->ai_gaps);
        $this->assertEquals('No evidence of multi-factor authentication enforcement', $result->ai_gaps[0]['gap']);
        $this->assertEquals('high', $result->ai_gaps[0]['severity']);
        $this->assertEquals('Screen lock timeout not documented', $result->ai_gaps[1]['gap']);
        $this->assertEquals('medium', $result->ai_gaps[1]['severity']);
    }

    public function test_service_stores_empty_gaps_when_no_gaps_found(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"observations":"1. File provides a complete MFA configuration document.\\n2. All access controls are properly documented.","recommendations":"1. Continue regular review cycle.","gaps":[]}']]]]
                ]
            ], 200),
            'localhost:9000/*' => Http::response(['infected' => false], 200),
        ]);

        Storage::fake('public');
        $path = 'evidence/' . $this->project->id . '/mfa-config.pdf';
        Storage::disk('public')->put($path, 'fake pdf content for testing');

        $evidence = EvidenceFile::create([
            'project_id' => $this->project->id,
            'user_id' => $this->admin->id,
            'framework_control_id' => $this->control->id,
            'file_path' => $path,
            'original_filename' => 'mfa-config.pdf',
            'mime_type' => 'application/pdf',
            'scan_status' => 'pending',
            'ai_analysis_status' => 'pending',
        ]);

        $service = app(DirectEvidenceAnalysisService::class);
        $result = $service->process($evidence);

        $this->assertEquals('awaiting_review', $result->ai_analysis_status);
        $this->assertIsArray($result->ai_gaps);
        $this->assertCount(0, $result->ai_gaps);
    }

    public function test_gaps_do_not_block_approve(): void
    {
        $evidence = EvidenceFile::create([
            'project_id' => $this->project->id,
            'user_id' => $this->admin->id,
            'framework_control_id' => $this->control->id,
            'file_path' => 'evidence/test/test.pdf',
            'original_filename' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'scan_status' => 'clean',
            'ai_observations' => 'Test observation',
            'ai_recommendations' => 'Test recommendation',
            'ai_gaps' => [['gap' => 'MFA not documented', 'severity' => 'high']],
            'ai_analysis_status' => 'awaiting_review',
            'hitl_status' => 'pending_review',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('evidence.ai.approve', $evidence));

        $response->assertStatus(200);
        $this->assertDatabaseHas('evidence_files', [
            'id' => $evidence->id,
            'ai_analysis_status' => 'approved',
        ]);
    }

    public function test_hub_page_shows_gap_badge(): void
    {
        EvidenceFile::create([
            'project_id' => $this->project->id,
            'user_id' => $this->admin->id,
            'framework_control_id' => $this->control->id,
            'file_path' => 'evidence/test/mfa.pdf',
            'original_filename' => 'mfa-policy.pdf',
            'mime_type' => 'application/pdf',
            'scan_status' => 'clean',
            'ai_observations' => 'Test observation',
            'ai_recommendations' => 'Test recommendation',
            'ai_gaps' => [
                ['gap' => 'No MFA enforcement documented', 'severity' => 'high'],
                ['gap' => 'Password rotation not evidenced', 'severity' => 'medium'],
            ],
            'ai_analysis_status' => 'awaiting_review',
            'hitl_status' => 'pending_review',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('evidence.hub', $this->project));

        $response->assertStatus(200);
        $response->assertSee('AI Gaps Detected');
        $response->assertSee('No MFA enforcement documented');
        $response->assertSee('Password rotation not evidenced');
        $response->assertSee('AI-generated, please verify');
    }

    public function test_hub_page_does_not_show_gap_badge_when_no_gaps(): void
    {
        EvidenceFile::create([
            'project_id' => $this->project->id,
            'user_id' => $this->admin->id,
            'framework_control_id' => $this->control->id,
            'file_path' => 'evidence/test/complete.pdf',
            'original_filename' => 'complete-evidence.pdf',
            'mime_type' => 'application/pdf',
            'scan_status' => 'clean',
            'ai_observations' => 'All controls satisfied',
            'ai_recommendations' => 'None',
            'ai_gaps' => [],
            'ai_analysis_status' => 'awaiting_review',
            'hitl_status' => 'pending_review',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('evidence.hub', $this->project));

        $response->assertStatus(200);
        $response->assertSee('ai_gaps');
        $response->assertDontSee('No MFA enforcement');
        $response->assertDontSee('Password rotation');
    }

    public function test_status_endpoint_returns_gaps(): void
    {
        $evidence = EvidenceFile::create([
            'project_id' => $this->project->id,
            'user_id' => $this->admin->id,
            'framework_control_id' => $this->control->id,
            'file_path' => 'evidence/test/gap.pdf',
            'original_filename' => 'gap-evidence.pdf',
            'mime_type' => 'application/pdf',
            'scan_status' => 'clean',
            'ai_observations' => 'Test',
            'ai_recommendations' => 'Test',
            'ai_gaps' => [['gap' => 'Firewall rules not evidenced', 'severity' => 'high']],
            'ai_analysis_status' => 'awaiting_review',
            'hitl_status' => 'pending_review',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('evidence.get-status', $evidence));

        $response->assertStatus(200);
        $response->assertJsonPath('gaps.0.gap', 'Firewall rules not evidenced');
        $response->assertJsonPath('gaps.0.severity', 'high');
    }

    public function test_incomplete_submission_produces_specific_gaps(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"observations":"1. The document describes a generic password policy.\\n2. No screenshots or configuration exports are included.","recommendations":"1. Provide a directory services screenshot showing MFA enforcement.\\n2. Include a configuration export of the access control system.","gaps":[{"gap":"No evidence of multi-factor authentication enforcement on remote access","severity":"high"},{"gap":"Active Directory group policy objects not provided","severity":"high"},{"gap":"No screenshots of access control configuration panels","severity":"medium"}]}']]]]
                ]
            ], 200),
            'localhost:9000/*' => Http::response(['infected' => false], 200),
        ]);

        Storage::fake('public');
        $path = 'evidence/' . $this->project->id . '/incomplete-submission.pdf';
        Storage::disk('public')->put($path, 'fake pdf content for testing');

        $evidence = EvidenceFile::create([
            'project_id' => $this->project->id,
            'user_id' => $this->admin->id,
            'framework_control_id' => $this->control->id,
            'file_path' => $path,
            'original_filename' => 'incomplete-submission.pdf',
            'mime_type' => 'application/pdf',
            'scan_status' => 'pending',
            'ai_analysis_status' => 'pending',
        ]);

        $service = app(DirectEvidenceAnalysisService::class);
        $result = $service->process($evidence);

        $this->assertCount(3, $result->ai_gaps);
        $this->assertEquals('No evidence of multi-factor authentication enforcement on remote access', $result->ai_gaps[0]['gap']);
        $this->assertEquals('high', $result->ai_gaps[0]['severity']);
        $this->assertStringContainsString('Active Directory', $result->ai_gaps[1]['gap']);
        $this->assertStringContainsString('configuration', $result->ai_gaps[2]['gap']);
    }
}
