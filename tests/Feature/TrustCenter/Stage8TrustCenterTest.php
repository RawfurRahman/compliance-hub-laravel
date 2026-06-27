<?php

namespace Tests\Feature\TrustCenter;

use App\Models\Framework;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\User;
use App\Modules\TrustCenter\Models\TrustCenter;
use App\Modules\TrustCenter\Models\TrustCenterAccessRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Stage8TrustCenterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected TrustCenter $trustCenter;

    protected function setUp(): void
    {
        parent::setUp();

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

    private function createFramework(string $name, string $slug): Framework
    {
        return Framework::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function createAssessment(Framework $framework, bool $publiclyVisible): ProjectAssessment
    {
        return ProjectAssessment::create([
            'project_id' => $this->project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
            'overall_status' => 'in_progress',
            'is_publicly_visible' => $publiclyVisible,
        ]);
    }

    public function test_published_trust_center_renders_without_auth(): void
    {
        $response = $this->get(route('trust-center.public.show', $this->trustCenter->public_slug));

        $response->assertStatus(200);
        $response->assertSee('Security at Test Company');
        $response->assertSee('We take security seriously.');
    }

    public function test_unpublished_trust_center_shows_not_available(): void
    {
        $this->trustCenter->update(['is_published' => false]);

        $response = $this->get(route('trust-center.public.show', $this->trustCenter->public_slug));

        $response->assertStatus(404);
        $response->assertSee('Not Available');
    }

    public function test_nonexistent_slug_shows_404(): void
    {
        $response = $this->get('/trust/nonexistent-slug-12345');

        $response->assertStatus(404);
    }

    public function test_only_publicly_visible_frameworks_are_shown(): void
    {
        $visibleFramework = $this->createFramework('ISO 27001', 'iso_27001');
        $hiddenFramework = $this->createFramework('PCI DSS', 'pci_dss');

        $this->createAssessment($visibleFramework, true);
        $this->createAssessment($hiddenFramework, false);

        $response = $this->get(route('trust-center.public.show', $this->trustCenter->public_slug));

        $response->assertStatus(200);
        $response->assertSee('ISO 27001');
        $response->assertDontSee('PCI DSS');
    }

    public function test_request_access_stores_submission(): void
    {
        $response = $this->post(
            route('trust-center.public.request-access', $this->trustCenter->public_slug),
            [
                'name'  => 'John Doe',
                'email' => 'requester@example.com',
                'note'  => 'I would like to see your compliance details.',
            ]
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('trust_center_access_requests', [
            'trust_center_id'  => $this->trustCenter->id,
            'requester_name'   => 'John Doe',
            'requester_email'  => 'requester@example.com',
            'note'             => 'I would like to see your compliance details.',
        ]);
    }

    public function test_no_project_data_leaks_on_public_page(): void
    {
        $this->createAssessment(
            $this->createFramework('SOC 2', 'soc2'),
            true
        );

        $response = $this->get(route('trust-center.public.show', $this->trustCenter->public_slug));

        $response->assertStatus(200);
        $response->assertDontSee($this->project->name);
        $response->assertDontSee('module_type');
        $response->assertDontSee('data-project-id');
        $response->assertDontSee('"project_id"');
        $response->assertDontSee("project_id={$this->project->id}");
    }

    public function test_admin_can_toggle_publish_status(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('admin.trust-centers.edit', $this->trustCenter))
            ->put(route('admin.trust-centers.update', $this->trustCenter), [
                'headline' => 'Updated Headline',
                'summary' => 'Updated summary.',
                'contact_email' => 'new@test.com',
                'is_published' => '0',
            ]);

        $response->assertRedirect(route('admin.trust-centers.edit', $this->trustCenter));
        $response->assertSessionHas('success');

        $this->assertFalse($this->trustCenter->fresh()->is_published);
    }

    public function test_admin_can_toggle_framework_visibility(): void
    {
        $fw = $this->createFramework('HIPAA', 'hipaa');
        $assessment = $this->createAssessment($fw, false);

        $response = $this->actingAs($this->user)
            ->put(route('admin.trust-centers.update', $this->trustCenter), [
                'headline' => 'Security at Test Company',
                'summary' => 'We take security seriously.',
                'is_published' => '1',
                'framework_visibility' => [$assessment->id => '1'],
            ]);

        $response->assertSessionHas('success');

        $this->assertTrue((bool) $assessment->fresh()->is_publicly_visible);

        $response = $this->actingAs($this->user)
            ->put(route('admin.trust-centers.update', $this->trustCenter), [
                'headline' => 'Security at Test Company',
                'summary' => 'We take security seriously.',
                'is_published' => '1',
                'framework_visibility' => [],
            ]);

        $this->assertFalse((bool) $assessment->fresh()->is_publicly_visible);
    }
}
