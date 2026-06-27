<?php

namespace Tests\Feature\TrustCenter;

use App\Models\EvidenceFile;
use App\Models\Framework;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\User;
use App\Modules\TrustCenter\Models\TrustCenter;
use App\Modules\TrustCenter\Models\TrustCenterAccessRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Stage10TrustCenterEvidenceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected Project $project;
    protected TrustCenter $trustCenter;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('evidence_files');
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

        $adminRoleId = DB::table('roles')->insertGetId(['name' => 'Admin']);
        $customerRoleId = DB::table('roles')->insertGetId(['name' => 'Customer']);

        $this->admin = User::factory()->create([
            'username' => 'testadmin',
            'email' => 'admin@test.com',
        ]);
        $this->customer = User::factory()->create([
            'username' => 'testcustomer',
            'email' => 'customer@test.com',
        ]);

        DB::table('user_roles')->insert([
            ['user_id' => $this->admin->id, 'role_id' => $adminRoleId],
            ['user_id' => $this->customer->id, 'role_id' => $customerRoleId],
        ]);

        $this->project = Project::create([
            'name' => 'Test Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->admin->id,
        ]);

        $this->trustCenter = TrustCenter::create([
            'project_id' => $this->project->id,
            'is_published' => true,
            'headline' => 'Security at Test Company',
            'summary' => 'We take security seriously.',
            'contact_email' => 'security@test.com',
        ]);
    }

    private function createEvidenceFile(array $overrides = []): EvidenceFile
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('soc2-report.pdf', 100, 'application/pdf');

        $path = $file->store('evidence', 'public');

        return EvidenceFile::create(array_merge([
            'project_id'          => $this->project->id,
            'user_id'             => $this->admin->id,
            'file_path'           => $path,
            'original_filename'   => 'soc2-report.pdf',
            'mime_type'           => 'application/pdf',
            'trust_center_id'     => $this->trustCenter->id,
            'is_publicly_listed'  => true,
        ], $overrides));
    }

    public function test_admin_can_toggle_evidence_listing_on_trust_center(): void
    {
        $evidenceFile = $this->createEvidenceFile(['is_publicly_listed' => false]);

        $response = $this->actingAs($this->admin)
            ->post(route('evidence.trust-center-toggle', $evidenceFile), [
                'is_publicly_listed' => true,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertTrue((bool) $evidenceFile->fresh()->is_publicly_listed);
        $this->assertEquals($this->trustCenter->id, $evidenceFile->fresh()->trust_center_id);

        $response = $this->actingAs($this->admin)
            ->post(route('evidence.trust-center-toggle', $evidenceFile), [
                'is_publicly_listed' => false,
            ]);

        $this->assertFalse((bool) $evidenceFile->fresh()->is_publicly_listed);
        $this->assertNull($evidenceFile->fresh()->trust_center_id);
    }

    public function test_non_admin_cannot_toggle_evidence_listing(): void
    {
        $evidenceFile = $this->createEvidenceFile(['is_publicly_listed' => false]);

        $response = $this->actingAs($this->customer)
            ->post(route('evidence.trust-center-toggle', $evidenceFile), [
                'is_publicly_listed' => true,
            ]);

        $response->assertStatus(403);
    }

    public function test_public_page_shows_only_evidence_name_and_type(): void
    {
        $this->createEvidenceFile();

        $response = $this->get(route('trust-center.public.show', $this->trustCenter->public_slug));

        $response->assertStatus(200);
        $response->assertSee('soc2-report.pdf');
        $response->assertSee('APPLICATION/PDF');
        $response->assertDontSee('file_path');
        $response->assertDontSee('trust_center_id');
    }

    public function test_file_not_downloadable_by_unapproved_visitor(): void
    {
        $evidenceFile = $this->createEvidenceFile();

        $response = $this->get('/api/evidence/file/' . $evidenceFile->id);

        $response->assertStatus(403);
    }

    public function test_file_downloadable_after_request_approved(): void
    {
        $evidenceFile = $this->createEvidenceFile();

        $this->post(
            route('trust-center.public.request-access', $this->trustCenter->public_slug),
            [
                'name'  => 'Jane Requester',
                'email' => 'jane@example.com',
                'note'  => 'Please grant access.',
            ]
        );

        TrustCenterAccessRequest::where('requester_email', 'jane@example.com')
            ->update(['status' => 'Approved']);

        $response = $this->get('/api/evidence/file/' . $evidenceFile->id);

        $response->assertStatus(200);
    }

    public function test_public_page_shows_download_link_only_when_approved(): void
    {
        $this->createEvidenceFile();

        $response = $this->get(route('trust-center.public.show', $this->trustCenter->public_slug));
        $response->assertSee('Request access to download');
        $response->assertDontSee('/api/evidence/file/');

        $this->post(
            route('trust-center.public.request-access', $this->trustCenter->public_slug),
            [
                'name'  => 'Jane Requester',
                'email' => 'jane@example.com',
                'note'  => 'Please grant access.',
            ]
        );

        TrustCenterAccessRequest::where('requester_email', 'jane@example.com')
            ->update(['status' => 'Approved']);

        $response = $this->get(route('trust-center.public.show', $this->trustCenter->public_slug));
        $response->assertSee('/api/evidence/file/');
        $response->assertDontSee('Request access to download');
    }
}
