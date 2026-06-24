<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Department;
use App\Models\Asset;
use App\Models\User;
use App\Models\IsoGapAssessment;
use App\Models\PciGapAssessment;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Services\WorkbookImportService;
use App\Modules\RiskManagement\Services\MigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RiskRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;
    protected $department;
    protected $control;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->department = Department::create([
            'name' => 'IT Security Department',
        ]);

        $this->project = Project::create([
            'name' => 'Risk Test Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);

        $framework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'version' => '2022',
            'is_active' => true,
        ]);

        $this->control = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.5.1',
            'domain' => 'Policies for information security',
            'requirement_description' => 'Information security policies should be defined.',
        ]);
    }

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get(route('risk-register.index', $this->project));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_access_risk_register_index()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('risk-register.index', $this->project));
        $response->assertStatus(200);
        $response->assertViewIs('risk-management.register');
        $response->assertSee('Integrated Risk Register');
    }

    public function test_user_can_store_risk_entry()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('risk-register.store', $this->project), [
            'serial_no' => 'RISK-001',
            'asset_process_service' => 'Unauthorized Access to Server Rooms',
            'risk_owner' => 'Jane Doe',
            'department' => $this->department->name,
            'risk_calculation_date' => '2026-06-22',
            'asset_value_bdt' => 100000.00,
            'category' => 'Cybersecurity',
            'threats' => '["Physical intrusion"]',
            'threat_level_t' => 4,
            'vulnerabilities' => '["Unlocked server room"]',
            'vulnerability_level_av' => 4,
            'likelihood_lh' => 4,
            'residual_tv' => 2,
            'residual_lh' => 2,
            'measurement' => 'Accepted',
            'impact_confidentiality' => 4,
            'impact_integrity' => 4,
            'impact_availability' => 4,
            'existing_control' => 'Locks and badges',
        ]);

        if ($response->status() !== 302) {
            $response->dump();
        }

        $response->assertRedirect();

        $this->assertDatabaseHas('risk_registers', [
            'project_id' => $this->project->id,
            'serial_no' => 'RISK-001',
            'asset_process_service' => 'Unauthorized Access to Server Rooms',
            'risk_rating_avtvlh' => 128, // vulnerability (4) * TV (4 + 4 = 8) * Likelihood (4) = 128
            'computed_risk_rating' => 128,
            'residual_rating' => 4, // 2 * 2 = 4
            'computed_residual_rating' => 4,
        ]);
    }

    public function test_user_can_update_risk_entry()
    {
        $this->actingAs($this->user);

        $risk = RiskRegister::create([
            'serial_no' => 'RISK-001',
            'project_id' => $this->project->id,
            'asset_process_service' => 'SQL Injection Vulnerability',
            'risk_owner' => 'John Doe',
            'department' => $this->department->name,
            'risk_calculation_date' => '2026-06-22',
            'category' => 'Cybersecurity',
            'asset_value_bdt' => 50000.00,
            'threats' => ['SQL Injection'],
            'threat_level_t' => 3,
            'vulnerabilities' => ['Unsanitized inputs'],
            'vulnerability_level_av' => 4,
            'tv_t_av' => 7,
            'likelihood_lh' => 3,
            'risk_rating_avtvlh' => 84,
            'residual_tv' => 2,
            'residual_lh' => 2,
            'residual_rating' => 4,
            'measurement' => 'Not Accepted',
            'impact_confidentiality' => 5,
            'impact_integrity' => 5,
            'impact_availability' => 5,
            'existing_control' => 'Firewall',
        ]);

        $response = $this->put(route('risk-register.update', [$this->project, $risk]), [
            'serial_no' => 'RISK-001',
            'asset_process_service' => 'Updated SQL Injection Vulnerability',
            'risk_owner' => 'John Doe',
            'department' => $this->department->name,
            'risk_calculation_date' => '2026-06-22',
            'category' => 'Cybersecurity',
            'asset_value_bdt' => 50000.00,
            'threat_level_t' => 4, // Changed from 3 to 4
            'vulnerability_level_av' => 4,
            'likelihood_lh' => 4, // Changed from 3 to 4
            'residual_tv' => 1,
            'residual_lh' => 2,
            'impact_confidentiality' => 5,
            'impact_integrity' => 5,
            'impact_availability' => 5,
            'existing_control' => 'Firewall and filters',
        ]);

        if ($response->status() !== 302) {
            $response->dump();
        }

        $response->assertRedirect();

        $this->assertDatabaseHas('risk_registers', [
            'id' => $risk->id,
            'asset_process_service' => 'Updated SQL Injection Vulnerability',
            'risk_rating_avtvlh' => 128, // Vuln (4) * TV (4 + 4 = 8) * Likelihood (4) = 128
            'computed_risk_rating' => 128,
            'residual_rating' => 2,
            'computed_residual_rating' => 2,
        ]);
    }

    public function test_user_can_transition_risk_status()
    {
        $this->actingAs($this->user);

        $risk = RiskRegister::create([
            'serial_no' => 'RISK-001',
            'project_id' => $this->project->id,
            'asset_process_service' => 'SQL Injection Vulnerability',
            'risk_owner' => 'John Doe',
            'department' => $this->department->name,
            'risk_calculation_date' => '2026-06-22',
            'category' => 'Cybersecurity',
            'asset_value_bdt' => 50000.00,
            'threats' => ['SQL Injection'],
            'threat_level_t' => 3,
            'vulnerabilities' => ['Unsanitized inputs'],
            'vulnerability_level_av' => 4,
            'tv_t_av' => 7,
            'likelihood_lh' => 3,
            'risk_rating_avtvlh' => 84,
            'residual_tv' => 2,
            'residual_lh' => 2,
            'residual_rating' => 4,
            'measurement' => 'Not Accepted',
            'impact_confidentiality' => 5,
            'impact_integrity' => 5,
            'impact_availability' => 5,
            'existing_control' => 'Firewall',
            'implementation_status' => 'Not Started',
        ]);

        $response = $this->postJson(route('risk-register.transition', [$this->project, $risk]), [
            'status' => 'In Progress',
            'reason' => 'Starting controls deployment.',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('In Progress', $risk->fresh()->implementation_status);
        $this->assertDatabaseHas('activity_log', [
            'action' => 'risk_status_changed',
        ]);
    }

    public function test_map_and_unmap_framework_control()
    {
        $this->actingAs($this->user);

        $risk = RiskRegister::create([
            'serial_no' => 'RISK-001',
            'project_id' => $this->project->id,
            'asset_process_service' => 'SQL Injection Vulnerability',
            'risk_owner' => 'John Doe',
            'department' => $this->department->name,
            'risk_calculation_date' => '2026-06-22',
            'category' => 'Cybersecurity',
            'asset_value_bdt' => 50000.00,
            'threats' => ['SQL Injection'],
            'threat_level_t' => 3,
            'vulnerabilities' => ['Unsanitized inputs'],
            'vulnerability_level_av' => 4,
            'tv_t_av' => 7,
            'likelihood_lh' => 3,
            'risk_rating_avtvlh' => 84,
            'residual_tv' => 2,
            'residual_lh' => 2,
            'residual_rating' => 4,
            'measurement' => 'Not Accepted',
            'impact_confidentiality' => 5,
            'impact_integrity' => 5,
            'impact_availability' => 5,
            'existing_control' => 'Firewall',
        ]);

        // Map
        $response = $this->postJson(route('risk-register.map-control', [$this->project, $risk]), [
            'framework_control_id' => $this->control->id,
            'notes' => 'Preventive control mappings',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('risk_control_mappings', [
            'risk_register_id' => $risk->id,
            'framework_control_id' => $this->control->id,
            'notes' => 'Preventive control mappings',
        ]);

        // Unmap
        $response = $this->deleteJson(route('risk-register.unmap-control', [$this->project, $risk, $this->control->id]));
        $response->assertStatus(200);
        $this->assertDatabaseMissing('risk_control_mappings', [
            'risk_register_id' => $risk->id,
            'framework_control_id' => $this->control->id,
        ]);
    }

    public function test_add_comment_to_risk()
    {
        $this->actingAs($this->user);

        $risk = RiskRegister::create([
            'serial_no' => 'RISK-001',
            'project_id' => $this->project->id,
            'asset_process_service' => 'SQL Injection Vulnerability',
            'risk_owner' => 'John Doe',
            'department' => $this->department->name,
            'risk_calculation_date' => '2026-06-22',
            'category' => 'Cybersecurity',
            'asset_value_bdt' => 50000.00,
            'threats' => ['SQL Injection'],
            'threat_level_t' => 3,
            'vulnerabilities' => ['Unsanitized inputs'],
            'vulnerability_level_av' => 4,
            'tv_t_av' => 7,
            'likelihood_lh' => 3,
            'risk_rating_avtvlh' => 84,
            'residual_tv' => 2,
            'residual_lh' => 2,
            'residual_rating' => 4,
            'measurement' => 'Not Accepted',
            'impact_confidentiality' => 5,
            'impact_integrity' => 5,
            'impact_availability' => 5,
            'existing_control' => 'Firewall',
        ]);

        $response = $this->postJson(route('risk-register.comment', [$this->project, $risk]), [
            'body' => 'Spoke to developers, fix will be live tomorrow.',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('risk_comments', [
            'risk_register_id' => $risk->id,
            'body' => 'Spoke to developers, fix will be live tomorrow.',
        ]);
    }

    public function test_heatmap_loads_correct_metrics()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('risk-register.heatmap', $this->project));
        $response->assertStatus(200);
        $response->assertViewIs('risk-management.heatmap');
    }

    public function test_exports_endpoints_are_reachable()
    {
        $this->actingAs($this->user);

        // PDF
        $response = $this->get(route('risk-register.export-pdf', $this->project));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');

        // CSV
        $response = $this->get(route('risk-register.export-csv', $this->project));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_workbook_importer_service()
    {
        $this->actingAs($this->user);

        // Create Excel dynamically in memory
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        
        // Setup Control Mapping sheet
        $sheetMapping = $spreadsheet->createSheet();
        $sheetMapping->setTitle('Control Mapping');
        $sheetMapping->setCellValue('A2', 'PCI DSS Ref');
        $sheetMapping->setCellValue('B2', 'PCI DSS Requirement Description');
        $sheetMapping->setCellValue('D2', 'ISO 27001 Ref');
        $sheetMapping->setCellValue('E2', 'ISO 27001 Requirement Description');
        $sheetMapping->setCellValue('G2', 'BB ICT Ref');
        $sheetMapping->setCellValue('H2', 'BB ICT Description');
        $sheetMapping->setCellValue('J2', 'SWIFT CSCF Ref');
        $sheetMapping->setCellValue('K2', 'SWIFT CSCF Description');
        
        $sheetMapping->setCellValue('A3', '1.2.1');
        $sheetMapping->setCellValue('B3', 'Firewall description');
        $sheetMapping->setCellValue('D3', 'A.5.1'); // This will match our setup control
        $sheetMapping->setCellValue('E3', 'Policies for information security');
        $sheetMapping->setCellValue('G3', 'BB-1.1');
        $sheetMapping->setCellValue('H3', 'BB guide description');
        $sheetMapping->setCellValue('J3', 'CSCF-1.2');
        $sheetMapping->setCellValue('K3', 'CSCF description');

        // Setup Risk Register sheet
        $sheetRegister = $spreadsheet->getActiveSheet();
        $sheetRegister->setTitle('Risk Register');
        
        $headers = [
            '#', 'Asset / Process / Service', 'Risk Owner', 'Date', 'Asset Value (BDT)',
            'Threat', 'Threat Level (T)', 'Vulnerability', 'Impact C', 'Impact I', 'Impact A',
            'Existing Control', 'Vuln. Level (AV)', 'TV (T+AV)', 'Likelihood (LH)',
            'Risk Rating (AV*TV*LH)', 'Measurement', 'Proposed Control', 'Communication',
            'Impl. From', 'Impl. To', 'Impl. Status', 'Residual TV', 'Residual LH', 'Residual Rating', 'Follow-up Note'
        ];
        
        foreach ($headers as $colIdx => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheetRegister->setCellValue($colLetter . '3', $header);
        }
        
        $rowData = [
            '1', 'Customer Data Management', 'IT Security Department', '2026-06-22', '100000',
            'Data Breach', '4', 'Unencrypted storage', '4', '4', '4',
            'Firewall', '4', '8', '4',
            '128', 'Not Accepted', 'Encrypt database', 'Email communication',
            '2026-06-22', '2026-07-22', 'In Progress', '2', '2', '4', 'Ensure keys are rotated'
        ];
        
        foreach ($rowData as $colIdx => $val) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheetRegister->setCellValue($colLetter . '4', $val);
        }
        
        $tempPath = tempnam(sys_get_temp_dir(), 'risk_import_test') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        // Upload dry-run test
        $uploadedFile = new UploadedFile($tempPath, 'risk_register.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        
        $response = $this->postJson(route('risk-register.import.dry-run', $this->project), [
            'file' => $uploadedFile,
        ]);

        if ($response->status() !== 200) {
            $response->dump();
        }

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'temp_file',
            'suggested_mappings',
            'validation_rows',
        ]);

        $tempFile = $response->json('temp_file');
        $suggestedMappings = $response->json('suggested_mappings');

        // Confirm import test
        $confirmResponse = $this->postJson(route('risk-register.import.confirm', $this->project), [
            'temp_file' => $tempFile,
            'mappings' => $suggestedMappings,
        ]);

        if ($confirmResponse->status() !== 200) {
            $confirmResponse->dump();
        }

        $confirmResponse->assertStatus(200);
        $confirmResponse->assertJson(['success' => true]);

        // Check if database contains the imported risk, linked to IT Security Department
        $this->assertDatabaseHas('risk_registers', [
            'project_id' => $this->project->id,
            'serial_no' => '1',
            'asset_process_service' => 'Customer Data Management',
            'risk_owner' => 'IT Security Department',
            'risk_rating_avtvlh' => 128,
            'computed_risk_rating' => 128,
            'residual_rating' => 4,
            'computed_residual_rating' => 4,
        ]);

        // Verify that SWIFT control CSCF-1.2 got created automatically
        $swift = Framework::where('slug', 'swift_cscf')->first();
        $this->assertNotNull($swift);
        $this->assertDatabaseHas('framework_controls', [
            'framework_id' => $swift->id,
            'control_id' => 'CSCF-1.2',
        ]);

        unlink($tempPath);
    }

    public function test_migration_service()
    {
        $this->actingAs($this->user);

        // Create legacy records
        $iso = IsoGapAssessment::create([
            'project_id' => $this->project->id,
            'serial_no' => '1',
            'clause_reference' => '5.1',
            'observation_title' => 'ISO Weakness Observation',
            'risk_rating' => 'High',
            'current_state' => 'No guidelines',
            'gap_description' => 'Missing policy documentation',
            'impact_risk' => 'Information theft',
            'recommendation' => 'Write a policy document',
            'status' => 'In Progress',
        ]);

        $pci = PciGapAssessment::create([
            'project_id' => $this->project->id,
            'requirement_text' => 'Enforce strong passwords',
            'is_section_header' => false,
            'status' => 'Yes', // allowed enum value in SQLite constraint
            'comments' => 'Password policy active',
        ]);

        $service = new MigrationService();
        $result = $service->migrateLegacyAssessments($this->project->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['iso_migrated']);
        $this->assertEquals(1, $result['pci_migrated']);

        // Verify database contains the ISO risk
        $this->assertDatabaseHas('risk_registers', [
            'project_id' => $this->project->id,
            'legacy_source_id' => 'iso_gap_assessment_' . $iso->id,
            'asset_process_service' => 'ISO Weakness Observation',
            'risk_owner' => 'IT Security Department',
            'risk_rating_avtvlh' => 96, // 4 * (4+4) * 3 = 96
            'computed_risk_rating' => 96,
        ]);

        // Verify database contains the PCI risk
        $this->assertDatabaseHas('risk_registers', [
            'project_id' => $this->project->id,
            'legacy_source_id' => 'pci_gap_assessment_' . $pci->id,
            'asset_process_service' => 'Enforce strong passwords',
            'risk_owner' => 'PCI Compliance Team',
            'implementation_status' => 'Completed',
        ]);

        // Attempting to migrate again should not duplicate
        $secondResult = $service->migrateLegacyAssessments($this->project->id);
        $this->assertEquals(0, $secondResult['iso_migrated']);
        $this->assertEquals(0, $secondResult['pci_migrated']);
    }

    public function test_artisan_commands()
    {
        // 1. Create a dummy spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheetRegister = $spreadsheet->getActiveSheet();
        $sheetRegister->setTitle('Risk Register');
        $headers = ['#', 'Asset / Process / Service', 'Risk Owner', 'Date', 'Asset Value (BDT)', 'Threat', 'Threat Level (T)', 'Vulnerability', 'Vuln. Level (AV)', 'Likelihood (LH)', 'Residual TV', 'Residual LH'];
        foreach ($headers as $colIdx => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheetRegister->setCellValue($colLetter . '3', $header);
        }
        $rowData = ['2', 'Server Backup Process', 'IT Ops', '2026-06-22', '50000', 'Loss of Server Data', '3', 'No offsite copy', '3', '2', '2', '1'];
        foreach ($rowData as $colIdx => $val) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheetRegister->setCellValue($colLetter . '4', $val);
        }
        $tempPath = tempnam(sys_get_temp_dir(), 'command_import_test') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        // Run artisan import command
        $this->artisan('rmm:import', [
            'file' => $tempPath,
            '--project-id' => $this->project->id,
            '--preset' => 'workbook_predefined'
        ])->assertExitCode(0);

        $this->assertDatabaseHas('risk_registers', [
            'project_id' => $this->project->id,
            'serial_no' => '2',
            'asset_process_service' => 'Server Backup Process',
        ]);

        unlink($tempPath);

        // 2. Setup legacy ISO and PCI records to migrate
        IsoGapAssessment::create([
            'project_id' => $this->project->id,
            'serial_no' => '2',
            'clause_reference' => '5.2',
            'observation_title' => 'ISO Policy Weakness',
            'risk_rating' => 'Medium',
            'current_state' => 'Missing policy guidelines',
            'gap_description' => 'No policy',
            'impact_risk' => 'Information risk',
            'recommendation' => 'Publish guidelines',
            'status' => 'Closed',
        ]);

        // Run artisan migrate legacy command
        $this->artisan('rmm:migrate-legacy', [
            '--project-id' => $this->project->id
        ])->assertExitCode(0);

        $this->assertDatabaseHas('risk_registers', [
            'project_id' => $this->project->id,
            'asset_process_service' => 'ISO Policy Weakness',
            'implementation_status' => 'Completed',
        ]);
    }
}
