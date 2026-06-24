<?php

namespace Tests\Feature\Compliance;

use App\Models\Control;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\User;
use App\Modules\Compliance\Services\MappingImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MappingImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Control $control;
    protected FrameworkControl $fc1;
    protected FrameworkControl $fc2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        $framework = Framework::create(['name' => 'PCI DSS', 'slug' => 'pci_dss', 'is_active' => true]);
        $iso = Framework::create(['name' => 'ISO 27001', 'slug' => 'iso_27001', 'is_active' => true]);

        $this->control = Control::create(['control_code' => 'CTRL-A', 'name' => 'Access Control', 'is_active' => true]);
        $this->fc1 = FrameworkControl::create(['framework_id' => $framework->id, 'control_id' => 'PCI-1.1', 'domain' => 'General', 'requirement_description' => 'Test requirement']);
        $this->fc2 = FrameworkControl::create(['framework_id' => $iso->id, 'control_id' => 'A.5.1', 'domain' => 'General', 'requirement_description' => 'Test requirement']);
    }

    public function test_import_single_mapping(): void
    {
        $service = app(MappingImportService::class);
        $count = $service->importMappings([
            [
                'control_id' => $this->control->id,
                'framework_control_id' => $this->fc1->id,
                'mapping_type' => 'direct',
            ],
        ], $this->user->id);

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('comp_framework_control_map', [
            'control_id' => $this->control->id,
            'framework_control_id' => $this->fc1->id,
        ]);
    }

    public function test_import_skips_invalid_control(): void
    {
        $service = app(MappingImportService::class);
        $count = $service->importMappings([
            [
                'control_id' => 9999,
                'framework_control_id' => $this->fc1->id,
            ],
            [
                'control_id' => $this->control->id,
                'framework_control_id' => $this->fc2->id,
            ],
        ], $this->user->id);

        $this->assertEquals(1, $count);
    }

    public function test_import_creates_version_if_not_exists(): void
    {
        $service = app(MappingImportService::class);
        $service->importMappings([
            [
                'control_id' => $this->control->id,
                'framework_control_id' => $this->fc1->id,
                'version' => '4.0',
            ],
        ], $this->user->id);

        $this->assertDatabaseHas('comp_framework_versions', [
            'framework_id' => $this->fc1->framework_id,
            'version' => '4.0',
        ]);
    }

    public function test_get_mappings_for_control(): void
    {
        $service = app(MappingImportService::class);
        $service->importMappings([
            ['control_id' => $this->control->id, 'framework_control_id' => $this->fc1->id],
            ['control_id' => $this->control->id, 'framework_control_id' => $this->fc2->id],
        ], $this->user->id);

        $mappings = $service->getMappingsForControl($this->control->id);
        $this->assertCount(2, $mappings);
    }

    public function test_preview_mappings(): void
    {
        $service = app(MappingImportService::class);
        $results = $service->previewMappings([
            ['control_id' => $this->control->id, 'framework_control_id' => $this->fc1->id],
            ['control_id' => $this->control->id, 'framework_control_id' => $this->fc2->id],
        ]);

        $this->assertCount(2, $results);
        $this->assertEquals('Access Control', $results->first()['control_name']);
        $this->assertFalse($results->first()['exists']);
    }

    public function test_update_or_create_mapping_is_idempotent(): void
    {
        $service = app(MappingImportService::class);
        $service->importMappings([
            ['control_id' => $this->control->id, 'framework_control_id' => $this->fc1->id],
        ], $this->user->id);

        $service->importMappings([
            ['control_id' => $this->control->id, 'framework_control_id' => $this->fc1->id, 'mapping_notes' => 'Updated'],
        ], $this->user->id);

        $mappings = $service->getMappingsForControl($this->control->id);
        $this->assertCount(1, $mappings);
        $this->assertEquals('Updated', $mappings->first()->mapping_notes);
    }
}
