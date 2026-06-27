<?php

namespace Tests\Feature\Compliance;

use App\Models\Framework;
use App\Models\Project;
use App\Models\User;
use App\Modules\Compliance\Models\ComplianceTest;
use App\Modules\Compliance\Models\ComplianceTestTemplate;
use Database\Seeders\ComplianceTestTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectIntegrationAutoCreateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->project = Project::create([
            'name' => 'Test Project',
            'module_type' => 'pci_dss',
            'user_id' => $this->user->id,
        ]);

        Framework::create(['name' => 'PCI DSS', 'slug' => 'pci_dss', 'is_active' => true]);
        Framework::create(['name' => 'ISO 27001', 'slug' => 'iso_27001', 'is_active' => true]);

        $this->seed(ComplianceTestTemplateSeeder::class);
    }

    public function test_connecting_n8n_integration_auto_creates_compliance_tests(): void
    {
        $templateCount = ComplianceTestTemplate::where('integration_type', 'n8n')->count();
        $this->assertEquals(5, $templateCount, 'Expected 5 n8n test templates from seeder');

        $response = $this->actingAs($this->user)->post(
            route('compliance.integrations.store', $this->project),
            [
                'name' => 'My n8n Server',
                'type' => 'n8n',
                'config' => json_encode(['webhook_url' => 'http://n8n:5678/webhook/evidence-processing']),
            ]
        );

        $response->assertSessionHas('success');
        $response->assertRedirect(route('compliance.tests.index', $this->project));

        $this->assertDatabaseHas('integrations', [
            'project_id' => $this->project->id,
            'name' => 'My n8n Server',
            'type' => 'n8n',
            'is_active' => true,
        ]);

        $integrationId = $this->project->integrations()->first()->id;

        $createdTests = ComplianceTest::where('integration_id', $integrationId)->get();
        $this->assertCount(5, $createdTests, 'Expected 5 compliance tests auto-created');

        foreach ($createdTests as $test) {
            $this->assertEquals('Not Yet Run', $test->status);
            $this->assertEquals('Automated', $test->test_type);
            $this->assertEquals($this->user->id, $test->owner_user_id);
            $this->assertNotNull($test->integration_id);

            $frameworkLinks = $test->frameworkLinks;
            $this->assertCount(2, $frameworkLinks, 'Expected links to both active frameworks');

            $linkedFrameworkIds = $frameworkLinks->pluck('framework_id')->sort()->values()->toArray();
            $expectedIds = Framework::where('is_active', true)->pluck('id')->sort()->values()->toArray();
            $this->assertEquals($expectedIds, $linkedFrameworkIds);
        }
    }

    public function test_connecting_integration_with_no_templates_creates_zero_tests(): void
    {
        ComplianceTestTemplate::where('integration_type', 'n8n')->delete();

        $response = $this->actingAs($this->user)->post(
            route('compliance.integrations.store', $this->project),
            [
                'name' => 'AWS Connection',
                'type' => 'aws',
                'config' => json_encode(['region' => 'us-east-1']),
            ]
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('integrations', [
            'project_id' => $this->project->id,
            'name' => 'AWS Connection',
            'type' => 'aws',
        ]);

        $createdTests = ComplianceTest::where('integration_id', $this->project->integrations()->first()->id)->get();
        $this->assertCount(0, $createdTests);
    }

    public function test_templates_can_be_seeded_and_queried_by_type(): void
    {
        $n8nTemplates = ComplianceTestTemplate::where('integration_type', 'n8n')->get();

        $this->assertCount(5, $n8nTemplates);

        $names = $n8nTemplates->pluck('name')->toArray();
        $this->assertContains('Evidence Malware Scan Check', $names);
        $this->assertContains('AI Analysis Completion Check', $names);
        $this->assertContains('Evidence Processing SLA Check', $names);
        $this->assertContains('Malware Detection Alert', $names);
        $this->assertContains('Evidence Upload Freshness', $names);
    }
}
