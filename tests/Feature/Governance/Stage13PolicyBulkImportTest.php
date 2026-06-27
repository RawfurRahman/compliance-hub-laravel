<?php

namespace Tests\Feature\Governance;

use App\Models\Project;
use App\Models\User;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Stage13PolicyBulkImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('policy_versions');
        Schema::dropIfExists('policies');
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
        Schema::create('policies', function ($table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->string('policy_number')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->date('effective_date')->nullable();
            $table->date('expires_at')->nullable();
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->string('department', 100)->nullable();
            $table->string('business_unit', 100)->nullable();
            $table->integer('current_version')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('activity_log', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->text('description')->nullable();
            $table->text('details')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('role')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('policy_versions', function ($table) {
            $table->id();
            $table->foreignId('policy_id')->constrained()->cascadeOnDelete();
            $table->integer('version_number');
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->text('change_summary')->nullable();
            $table->string('status')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expires_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
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
    }

    public function test_bulk_upload_form_renders(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('governance.policies.bulk', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Bulk Import');
        $response->assertSee('files[]');
    }

    public function test_bulk_upload_processes_and_shows_review(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"title":"Acceptable Use Policy","description":"Rules for acceptable use of company systems.","approval_date":"2024-06-01","approver":"Jane Smith"}']]]]
                ]
            ], 200),
        ]);

        $file = UploadedFile::fake()->create('acceptable-use.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->post(route('governance.policies.bulk.upload', $this->project), [
                'files' => [$file],
            ]);

        $response->assertRedirect(route('governance.policies.bulk.review', $this->project));

        $reviewResponse = $this->actingAs($this->user)
            ->get(route('governance.policies.bulk.review', $this->project));

        $reviewResponse->assertStatus(200);
        $reviewResponse->assertSee('Acceptable Use Policy');
        $reviewResponse->assertSee('acceptable use of company systems');
        $reviewResponse->assertSee('Jane Smith');
        $reviewResponse->assertSee('2024-06-01');
        $reviewResponse->assertSee('All details recorded');
    }

    public function test_duplicate_title_detected(): void
    {
        Policy::create([
            'title' => 'Existing Data Privacy Policy',
            'status' => 'draft',
            'is_active' => true,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"title":"Existing Data Privacy Policy","description":"Covers data privacy.","approval_date":"2025-01-01","approver":"John Doe"}']]]]
                ]
            ], 200),
        ]);

        $file = UploadedFile::fake()->create('data-privacy.pdf', 100, 'application/pdf');

        $this->actingAs($this->user)
            ->post(route('governance.policies.bulk.upload', $this->project), [
                'files' => [$file],
            ]);

        $reviewResponse = $this->actingAs($this->user)
            ->get(route('governance.policies.bulk.review', $this->project));

        $reviewResponse->assertStatus(200);
        $reviewResponse->assertSee('Duplicate');
        $reviewResponse->assertSee('Existing Data Privacy Policy');
    }

    public function test_confirm_creates_policies_and_versions(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"title":"InfoSec Policy","description":"Information security policy.","approval_date":"2024-03-15","approver":"Alice"}']]]]
                ]
            ], 200),
        ]);

        $file = UploadedFile::fake()->create('infosec.pdf', 100, 'application/pdf');

        $this->actingAs($this->user)
            ->post(route('governance.policies.bulk.upload', $this->project), [
                'files' => [$file],
            ]);

        $this->assertEquals(0, Policy::count());

        $session = session('policy_bulk_import');
        $this->assertNotNull($session);
        $this->assertCount(1, $session);

        $response = $this->actingAs($this->user)
            ->post(route('governance.policies.bulk.confirm', $this->project), [
                'confirmed' => [$session[0]['id']],
                'items' => [
                    $session[0]['id'] => [
                        'title' => 'InfoSec Policy',
                        'description' => 'Information security policy.',
                        'approval_date' => '2024-03-15',
                        'approver' => 'Alice',
                    ],
                ],
            ]);

        $response->assertRedirect(route('governance.policies.index', $this->project));

        $this->assertEquals(1, Policy::count());
        $policy = Policy::first();
        $this->assertEquals('InfoSec Policy', $policy->title);
        $this->assertEquals('draft', $policy->status);

        $this->assertEquals(1, PolicyVersion::count());
        $version = PolicyVersion::first();
        $this->assertEquals(1, $version->version_number);
        $this->assertEquals('InfoSec Policy', $version->title);
        $this->assertStringContainsString('Alice', $version->content);
    }

    public function test_confirm_skips_unchecked_items(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"title":"Policy A","description":"First policy.","approval_date":"2024-01-01","approver":"A"}']]]]
                ]
            ], 200),
            '*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"title":"Policy B","description":"Second policy.","approval_date":"2024-02-01","approver":"B"}']]]]
                ]
            ], 200),
        ]);

        $files = [
            UploadedFile::fake()->create('policy-a.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('policy-b.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->user)
            ->post(route('governance.policies.bulk.upload', $this->project), [
                'files' => $files,
            ]);

        $session = session('policy_bulk_import');
        $this->assertCount(2, $session);

        $this->actingAs($this->user)
            ->post(route('governance.policies.bulk.confirm', $this->project), [
                'confirmed' => [$session[0]['id']],
                'items' => [
                    $session[0]['id'] => [
                        'title' => 'Policy A',
                        'description' => 'First policy.',
                        'approval_date' => '2024-01-01',
                        'approver' => 'A',
                    ],
                ],
            ]);

        $this->assertEquals(1, Policy::count());
        $this->assertEquals('Policy A', Policy::first()->title);
    }

    public function test_partial_fields_shows_details_missing(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"title":"Partial Policy","description":null,"approval_date":null,"approver":null}']]]]
                ]
            ], 200),
        ]);

        $file = UploadedFile::fake()->create('partial.pdf', 100, 'application/pdf');

        $this->actingAs($this->user)
            ->post(route('governance.policies.bulk.upload', $this->project), [
                'files' => [$file],
            ]);

        $reviewResponse = $this->actingAs($this->user)
            ->get(route('governance.policies.bulk.review', $this->project));

        $reviewResponse->assertStatus(200);
        $reviewResponse->assertSee('Details missing');
        $reviewResponse->assertSee('Partial Policy');
    }
}
