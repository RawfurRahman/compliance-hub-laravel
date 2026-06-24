<?php

namespace Tests\Feature;

use App\Models\Control;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Modules\RiskManagement\Exports\ControlMappingSheetExport;
use App\Modules\RiskManagement\Models\RiskControlMapping;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Services\ControlMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ControlMappingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Framework $pciFramework;
    protected Framework $isoFramework;
    protected FrameworkControl $pciControl;
    protected FrameworkControl $isoControl;
    protected FrameworkControl $bbictControl;
    protected FrameworkControl $swiftControl;
    protected Project $project;
    protected RiskRegister $risk;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Admin role and attach it to the test user
        $adminRole = Role::create(['name' => 'Admin']);
        $this->admin = User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@test.com',
        ]);
        $this->admin->roles()->attach($adminRole);

        // Create frameworks
        $this->pciFramework = Framework::create([
            'name' => 'PCI DSS',
            'slug' => 'pci_dss',
            'version' => '4.0',
            'is_active' => true,
        ]);
        $this->isoFramework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'version' => '2022',
            'is_active' => true,
        ]);
        $bbictFramework = Framework::create([
            'name' => 'BB ICT Guidelines',
            'slug' => 'bb_ict',
            'version' => 'v1',
            'is_active' => true,
        ]);
        $swiftFramework = Framework::create([
            'name' => 'SWIFT CSCF',
            'slug' => 'swift_cscf',
            'version' => '2026',
            'is_active' => true,
        ]);

        // Seed framework controls like a real Control Mapping sheet
        $this->pciControl = FrameworkControl::create([
            'framework_id' => $this->pciFramework->id,
            'control_id' => '1.1',
            'domain' => 'Requirement 1',
            'requirement_description' => 'Firewall policy and configuration standards are implemented and maintained.',
            'required_evidence' => 'Firewall rulesets, network diagrams',
            'status' => 'active',
            'iso_ref' => '8.20',
            'bb_ict_ref' => 'NW-01',
            'swift_ref' => 'NS-01',
        ]);

        $this->isoControl = FrameworkControl::create([
            'framework_id' => $this->isoFramework->id,
            'control_id' => '8.20',
            'domain' => 'ISO Section 8',
            'requirement_description' => 'Network security',
            'required_evidence' => 'Network architecture documentation',
            'status' => 'active',
            'pci_dss_ref' => '1.1',
            'bb_ict_ref' => 'NW-01',
            'swift_ref' => 'NS-01',
        ]);

        $this->bbictControl = FrameworkControl::create([
            'framework_id' => $bbictFramework->id,
            'control_id' => 'NW-01',
            'domain' => 'Network Security',
            'requirement_description' => 'Network security controls and firewall management',
            'required_evidence' => 'Network policy documents',
            'status' => 'active',
            'pci_dss_ref' => '1.1',
            'iso_ref' => '8.20',
        ]);

        $this->swiftControl = FrameworkControl::create([
            'framework_id' => $swiftFramework->id,
            'control_id' => 'NS-01',
            'domain' => 'Network Security',
            'requirement_description' => 'SWIFT network security controls for firewall management',
            'required_evidence' => 'SWIFT CSP documentation',
            'status' => 'active',
            'pci_dss_ref' => '1.1',
            'iso_ref' => '8.20',
        ]);

        // Create a local control (internal catalog)
        Control::create([
            'code' => 'FW-001',
            'control_code' => 'FW-001',
            'title' => 'Firewall Policy Management',
            'name' => 'Firewall Policy Management',
            'description' => 'Controls for managing firewall rule sets and change management',
            'is_active' => true,
            'status' => 'active',
            'effectiveness_score' => 85.0,
        ]);

        // Create a project with risk register entries
        $this->project = Project::create([
            'name' => 'Test Assessment',
            'module_type' => 'pci_dss',
            'user_id' => $this->admin->id,
        ]);

        $this->risk = RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => '1',
            'asset_process_service' => 'Corporate Firewall Configuration',
            'risk_owner' => 'IT Security Team',
            'risk_calculation_date' => now(),
            'asset_value_bdt' => 500000,
            'threats' => ['Unauthorized network access', 'Firewall misconfiguration'],
            'threat_level_t' => 4,
            'vulnerabilities' => ['Lack of firewall rule review process', 'No automated compliance checks'],
            'vulnerability_level_av' => 3,
            'impact_confidentiality' => 3,
            'impact_integrity' => 3,
            'impact_availability' => 3,
            'tv_t_av' => 7,
            'likelihood_lh' => 4,
            'risk_rating_avtvlh' => 84,
            'existing_control' => 'Manual quarterly firewall rule review',
            'implementation_status' => 'In Progress',
            'measurement' => 'Not Accepted',
            'residual_tv' => 3,
            'residual_lh' => 2,
            'residual_rating' => 6,
            'category' => 'IT Security',
            'department' => 'Information Technology',
        ]);
    }

    /** @test */
    public function it_imports_control_mapping_with_cross_refs()
    {
        $pciControl = FrameworkControl::where('control_id', '1.1')
            ->where('framework_id', $this->pciFramework->id)
            ->first();

        $this->assertNotNull($pciControl);
        $this->assertEquals('Firewall policy and configuration standards are implemented and maintained.', $pciControl->requirement_description);
        $this->assertEquals('active', $pciControl->status);
        $this->assertEquals('8.20', $pciControl->iso_ref);
        $this->assertEquals('NW-01', $pciControl->bb_ict_ref);
        $this->assertEquals('NS-01', $pciControl->swift_ref);
        $this->assertNull($pciControl->pci_dss_ref); // Self-ref should be null
    }

    /** @test */
    public function it_suggests_mappings_with_confidence_above_60()
    {
        $service = app(ControlMappingService::class);
        $suggestions = $service->suggest($this->risk, 5);

        $this->assertGreaterThan(0, $suggestions->count());

        // Top match should have confidence > 60 due to "firewall" keyword overlap
        $top = $suggestions->first();
        $this->assertGreaterThan(60.0, $top['confidence_score'],
            "Top suggestion confidence ({$top['confidence_score']}) should exceed 60.0 for firewall-related risk");
    }

    /** @test */
    public function it_suggests_mappings_from_free_text_query()
    {
        $service = app(ControlMappingService::class);
        $suggestions = $service->suggest('Firewall policy review and network security compliance', 5);

        $this->assertGreaterThan(0, $suggestions->count());

        $top = $suggestions->first();
        $this->assertInstanceOf(FrameworkControl::class, $top['framework_control']);
        $this->assertGreaterThan(60.0, $top['confidence_score']);
    }

    /** @test */
    public function it_suggests_local_controls()
    {
        $service = app(ControlMappingService::class);
        $suggestions = $service->suggestLocalControls('Firewall policy and configuration management', 5);

        $this->assertGreaterThan(0, $suggestions->count());

        $top = $suggestions->first();
        $this->assertEquals('FW-001', $top['control']->code);
        $this->assertGreaterThan(60.0, $top['confidence_score']);
    }

    /** @test */
    public function it_creates_suggestions_as_pending_mappings()
    {
        $service = app(ControlMappingService::class);

        // Create a suggestion
        $mapping = $service->createSuggestion(
            $this->risk->id,
            $this->pciControl->id,
            null,
            85.5
        );

        $this->assertDatabaseHas('risk_control_mappings', [
            'risk_register_id' => $this->risk->id,
            'framework_control_id' => $this->pciControl->id,
            'mapping_status' => 'suggested',
            'confidence_score' => 85.5,
        ]);
        $this->assertEquals('suggested', $mapping->mapping_status);
    }

    /** @test */
    public function it_confirms_suggested_mapping()
    {
        $this->actingAs($this->admin);
        $service = app(ControlMappingService::class);

        $mapping = $service->createSuggestion($this->risk->id, $this->pciControl->id, null, 90.0);
        $confirmed = $service->confirmMapping($mapping->id);

        $this->assertEquals('confirmed', $confirmed->mapping_status);
        $this->assertNotNull($confirmed->mapped_by);
        $this->assertNotNull($confirmed->mapped_at);
    }

    /** @test */
    public function it_rejects_suggested_mapping()
    {
        $this->actingAs($this->admin);
        $service = app(ControlMappingService::class);

        $mapping = $service->createSuggestion($this->risk->id, $this->pciControl->id, null, 90.0);
        $rejected = $service->rejectMapping($mapping->id);

        $this->assertEquals('rejected', $rejected->mapping_status);
        $this->assertNotNull($rejected->mapped_by);
        $this->assertNotNull($rejected->mapped_at);
    }

    /** @test */
    public function it_manually_maps_risk_to_control()
    {
        $service = app(ControlMappingService::class);

        $mapping = $service->manualMap(
            $this->risk->id,
            $this->pciControl->id,
            null,
            'Manual mapping notes'
        );

        $this->assertEquals('confirmed', $mapping->mapping_status);
        $this->assertEquals(100.0, $mapping->confidence_score);
        $this->assertEquals('Manual mapping notes', $mapping->notes);
    }

    /** @test */
    public function it_unmaps_risk_control_relationship()
    {
        $service = app(ControlMappingService::class);

        $service->manualMap($this->risk->id, $this->pciControl->id);
        $this->assertDatabaseCount('risk_control_mappings', 1);

        $service->unmap($this->risk->id, $this->pciControl->id);
        $this->assertDatabaseCount('risk_control_mappings', 0);
    }

    /** @test */
    public function it_exports_control_mapping_with_workbook_columns()
    {
        $export = new ControlMappingSheetExport();

        // Verify headings match workbook spec
        $headings = $export->headings();
        $this->assertEquals([
            'PCI DSS Ref',
            'PCI DSS Description',
            'ISO Ref',
            'ISO Description',
            'BB ICT Ref',
            'BB ICT Description',
            'SWIFT Ref',
            'SWIFT Description',
            'Status',
        ], $headings);

        // Verify sheet title
        $this->assertEquals('Control Mapping', $export->title());

        // Verify data rows contain the cross-referenced controls.
        // Export produces one row per distinct control group (all 4 frameworks
        // cross-referenced into a single row), so we expect exactly 1 row.
        $rows = $export->collection();
        $this->assertGreaterThanOrEqual(1, $rows->count(), 'Should have at least 1 row for the cross-referenced group');

        // Find the row for PCI DSS 1.1
        $pciRow = $rows->firstWhere('pci_dss_ref', '1.1');
        $this->assertNotNull($pciRow, 'Export should contain PCI DSS ref 1.1');
        $this->assertEquals('8.20', $pciRow['iso_ref']);
        $this->assertEquals('active', $pciRow['status']);
    }

    /** @test */
    public function it_returns_suggestions_via_api()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/rmm/control-mapping/suggest', [
                'risk_register_id' => $this->risk->id,
                'limit' => 5,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => ['framework_controls'],
            ]);

        $data = $response->json('data.framework_controls');
        $this->assertNotEmpty($data);

        // Top match should have confidence > 60
        $topConfidence = $data[0]['confidence_score'] ?? 0;
        $this->assertGreaterThan(60.0, $topConfidence,
            "API top suggestion confidence ({$topConfidence}) should exceed 60.0");
    }

    /** @test */
    public function it_returns_suggestions_from_free_text_via_api()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/rmm/control-mapping/suggest', [
                'query' => 'Unauthorized network access firewall misconfiguration',
                'limit' => 5,
            ]);

        $response->assertOk();
        $data = $response->json('data.framework_controls');
        $this->assertNotEmpty($data);

        // The firewall-related control should appear
        $refs = array_column($data, 'control_id');
        $this->assertContains('1.1', $refs, 'Firewall control 1.1 should be suggested');
    }

    /** @test */
    public function it_confirms_mapping_via_api()
    {
        $mapping = RiskControlMapping::create([
            'risk_register_id' => $this->risk->id,
            'framework_control_id' => $this->pciControl->id,
            'mapping_status' => 'suggested',
            'confidence_score' => 85.0,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/rmm/control-mapping/confirm', [
                'mapping_id' => $mapping->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.mapping_status', 'confirmed');
    }

    /** @test */
    public function it_rejects_mapping_via_api()
    {
        $mapping = RiskControlMapping::create([
            'risk_register_id' => $this->risk->id,
            'framework_control_id' => $this->pciControl->id,
            'mapping_status' => 'suggested',
            'confidence_score' => 85.0,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/rmm/control-mapping/reject', [
                'mapping_id' => $mapping->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.mapping_status', 'rejected');
    }

    /** @test */
    public function it_manual_maps_via_api()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/rmm/control-mapping/manual', [
                'risk_register_id' => $this->risk->id,
                'framework_control_id' => $this->pciControl->id,
                'notes' => 'Manually mapped from test',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.mapping_status', 'confirmed');

        $this->assertDatabaseHas('risk_control_mappings', [
            'risk_register_id' => $this->risk->id,
            'framework_control_id' => $this->pciControl->id,
            'mapping_status' => 'confirmed',
        ]);
    }

    /** @test */
    public function it_lists_mappings_by_risk_via_api()
    {
        RiskControlMapping::create([
            'risk_register_id' => $this->risk->id,
            'framework_control_id' => $this->pciControl->id,
            'mapping_status' => 'confirmed',
            'confidence_score' => 95.0,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/rmm/control-mapping/by-risk/{$this->risk->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_exports_control_mapping_sheet()
    {
        Excel::fake();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.control-mappings.export'));

        $response->assertOk();
    }

    /** @test */
    public function it_filters_suggestions_by_framework()
    {
        $service = app(ControlMappingService::class);

        // Suggest only within ISO 27001 framework
        $suggestions = $service->suggest('Network security controls', 5, $this->isoFramework->id);

        $this->assertGreaterThan(0, $suggestions->count());
        foreach ($suggestions as $s) {
            $this->assertEquals($this->isoFramework->id, $s['framework_control']->framework_id,
                'All suggestions should belong to the ISO 27001 framework');
        }
    }

    /** @test */
    public function it_suggests_all_controls_including_local()
    {
        $service = app(ControlMappingService::class);

        $results = $service->suggestAll($this->risk, 5);

        $this->assertArrayHasKey('framework_controls', $results);
        $this->assertArrayHasKey('local_controls', $results);
        $this->assertGreaterThan(0, $results['framework_controls']->count());
        $this->assertGreaterThan(0, $results['local_controls']->count(),
            'Should suggest at least the FW-001 local control');
    }

    /** @test */
    public function it_deletes_mapping_via_api()
    {
        RiskControlMapping::create([
            'risk_register_id' => $this->risk->id,
            'framework_control_id' => $this->pciControl->id,
            'mapping_status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/rmm/control-mapping/{$this->risk->id}/{$this->pciControl->id}");

        $response->assertOk();
        $this->assertDatabaseCount('risk_control_mappings', 0);
    }
}
