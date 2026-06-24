<?php

namespace Tests\Feature\Compliance;

use App\Models\Framework;
use App\Models\User;
use App\Modules\Compliance\Models\FrameworkVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrameworkVersionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Framework $framework;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->framework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'version' => '2022',
            'is_active' => true,
        ]);
    }

    public function test_can_create_framework_version(): void
    {
        $version = FrameworkVersion::create([
            'framework_id' => $this->framework->id,
            'version' => '2022',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('comp_framework_versions', ['version' => '2022']);
        $this->assertEquals('ISO 27001', $version->framework->name);
    }

    public function test_version_is_unique_per_framework(): void
    {
        FrameworkVersion::create([
            'framework_id' => $this->framework->id,
            'version' => '2022',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        FrameworkVersion::create([
            'framework_id' => $this->framework->id,
            'version' => '2022',
            'is_active' => true,
        ]);
    }

    public function test_active_scope(): void
    {
        FrameworkVersion::create([
            'framework_id' => $this->framework->id,
            'version' => '2013',
            'is_active' => false,
            'created_by' => $this->user->id,
        ]);
        FrameworkVersion::create([
            'framework_id' => $this->framework->id,
            'version' => '2022',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $active = FrameworkVersion::active()->get();
        $this->assertCount(1, $active);
        $this->assertEquals('2022', $active->first()->version);
    }

    public function test_belongs_to_framework(): void
    {
        $version = FrameworkVersion::create([
            'framework_id' => $this->framework->id,
            'version' => '2022',
            'created_by' => $this->user->id,
        ]);

        $this->assertNotNull($version->framework);
        $this->assertEquals($this->framework->id, $version->framework->id);
    }
}
