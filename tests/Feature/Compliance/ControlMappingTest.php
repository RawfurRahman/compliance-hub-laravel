<?php

namespace Tests\Feature\Compliance;

use App\Models\Control;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\User;
use App\Modules\Compliance\Models\FrameworkControlMap;
use App\Modules\Compliance\Models\FrameworkVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlMappingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Framework $framework;
    protected FrameworkControl $frameworkControl;
    protected FrameworkVersion $version;
    protected Control $control;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->framework = Framework::create(['name' => 'PCI DSS', 'slug' => 'pci_dss', 'is_active' => true]);
        $this->frameworkControl = FrameworkControl::create([
            'framework_id' => $this->framework->id,
            'control_id' => 'PCI-1.1',
            'domain' => 'Network Security',
            'requirement_description' => 'PCI requirement',
        ]);
        $this->control = Control::create([
            'control_code' => 'FW-001',
            'name' => 'Firewall Policy',
            'is_active' => true,
        ]);
        $this->version = FrameworkVersion::create([
            'framework_id' => $this->framework->id,
            'version' => '4.0',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_can_create_mapping(): void
    {
        $map = FrameworkControlMap::create([
            'control_id' => $this->control->id,
            'framework_control_id' => $this->frameworkControl->id,
            'framework_version_id' => $this->version->id,
            'mapping_type' => 'direct',
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('comp_framework_control_map', ['control_id' => $this->control->id]);
        $this->assertEquals('Firewall Policy', $map->control->name);
    }

    public function test_mapping_is_unique_per_control_and_framework_control(): void
    {
        FrameworkControlMap::create([
            'control_id' => $this->control->id,
            'framework_control_id' => $this->frameworkControl->id,
            'created_by' => $this->user->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        FrameworkControlMap::create([
            'control_id' => $this->control->id,
            'framework_control_id' => $this->frameworkControl->id,
        ]);
    }

    public function test_control_belongs_to_many_framework_controls(): void
    {
        FrameworkControlMap::create([
            'control_id' => $this->control->id,
            'framework_control_id' => $this->frameworkControl->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertCount(1, $this->control->frameworkControls);
        $this->assertEquals($this->frameworkControl->id, $this->control->frameworkControls->first()->id);
    }

    public function test_framework_control_belongs_to_many_controls(): void
    {
        FrameworkControlMap::create([
            'control_id' => $this->control->id,
            'framework_control_id' => $this->frameworkControl->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertCount(1, $this->frameworkControl->controls);
        $this->assertEquals($this->control->id, $this->frameworkControl->controls->first()->id);
    }

    public function test_one_control_maps_to_multiple_frameworks(): void
    {
        $framework2 = Framework::create(['name' => 'ISO 27001', 'slug' => 'iso_27001', 'is_active' => true]);
        $fc2 = FrameworkControl::create([
            'framework_id' => $framework2->id,
            'control_id' => 'A.5.1',
            'domain' => 'Security Policy',
            'requirement_description' => 'ISO requirement',
        ]);

        FrameworkControlMap::create(['control_id' => $this->control->id, 'framework_control_id' => $this->frameworkControl->id, 'created_by' => $this->user->id]);
        FrameworkControlMap::create(['control_id' => $this->control->id, 'framework_control_id' => $fc2->id, 'created_by' => $this->user->id]);

        $this->assertCount(2, $this->control->frameworkControls);
    }
}
