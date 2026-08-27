<?php

namespace Tests\Feature;

use App\Models\EvidenceFile;
use App\Models\EvidenceScanLog;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\EvidenceScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenceScanLogTest extends TestCase
{
    use RefreshDatabase;

    private const N8N_API_KEY = 'n8n_api_local_dev_key_12345';

    private function makeProjectAndEvidence(): array
    {
        $user = User::factory()->create();
        $suffix = uniqid();
        $project = Project::create([
            'name' => 'Scan Test Project '.$suffix,
            'module_type' => 'iso_27001',
            'user_id' => $user->id,
        ]);
        $framework = Framework::create([
            'name' => 'ISO 27001 '.$suffix,
            'slug' => 'iso_27001_'.$suffix,
            'is_active' => true,
        ]);
        $control = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.5.1',
            'domain' => 'Policies',
            'requirement_description' => 'Security policies.',
        ]);

        Storage::fake('public');
        Storage::fake('quarantine');
        Storage::disk('public')->put('evidence/test_'.$suffix.'.txt', 'clean file contents');

        $evidence = EvidenceFile::create([
            'project_id' => $project->id,
            'framework_control_id' => $control->id,
            'user_id' => $user->id,
            'file_path' => 'evidence/test_'.$suffix.'.txt',
            'original_filename' => 'test_'.$suffix.'.txt',
            'mime_type' => 'text/plain',
            'scan_status' => 'pending',
            'ai_analysis_status' => 'pending',
        ]);

        return [$project, $evidence, $user];
    }

    public function test_clean_scan_callback_records_scan_log_and_proceeds_to_ai()
    {
        [$project, $evidence] = $this->makeProjectAndEvidence();

        $response = $this->withHeader('X-N8n-Api-Key', self::N8N_API_KEY)
            ->postJson('/api/n8n/scan-callback', [
                'evidence_file_id' => $evidence->id,
                'scan_status' => 'clean',
                'scan_details' => ['infected' => false],
            ]);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $evidence->refresh();
        $this->assertEquals('clean', $evidence->scan_status);
        $this->assertEquals('processing', $evidence->ai_analysis_status);

        $this->assertDatabaseHas('evidence_scan_logs', [
            'evidence_file_id' => $evidence->id,
            'scan_status' => 'clean',
            'quarantined' => false,
        ]);
    }

    public function test_infected_scan_callback_quarantines_instead_of_deleting()
    {
        [$project, $evidence] = $this->makeProjectAndEvidence();

        $response = $this->withHeader('X-N8n-Api-Key', self::N8N_API_KEY)
            ->postJson('/api/n8n/scan-callback', [
                'evidence_file_id' => $evidence->id,
                'scan_status' => 'infected',
                'virus_name' => 'Eicar-Test-Signature',
                'scan_details' => ['infected' => true, 'virus' => 'Eicar-Test-Signature'],
            ]);

        $response->assertOk();
        $response->assertJson(['status' => 'security_action_taken']);

        // Evidence record is RETAINED (never deleted)
        $evidence->refresh();
        $this->assertNotNull($evidence);
        $this->assertEquals('infected', $evidence->scan_status);
        $this->assertEquals('skipped_due_to_scan', $evidence->ai_analysis_status);

        // Physical file moved out of public disk
        Storage::disk('public')->assertMissing('evidence/test.txt');

        // Scan log recorded with quarantine metadata
        $log = EvidenceScanLog::where('evidence_file_id', $evidence->id)->first();
        $this->assertNotNull($log);
        $this->assertTrue($log->quarantined);
        $this->assertEquals('Eicar-Test-Signature', $log->virus_name);
        $this->assertNotNull($log->quarantine_path);
        $this->assertTrue(Storage::disk('quarantine')->exists($log->quarantine_path));
    }

    public function test_failed_scan_callback_records_failed_log()
    {
        [$project, $evidence] = $this->makeProjectAndEvidence();

        $response = $this->withHeader('X-N8n-Api-Key', self::N8N_API_KEY)
            ->postJson('/api/n8n/scan-callback', [
                'evidence_file_id' => $evidence->id,
                'scan_status' => 'failed',
                'scan_details' => ['error' => 'scan engine error'],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('evidence_scan_logs', [
            'evidence_file_id' => $evidence->id,
            'scan_status' => 'failed',
        ]);
    }

    public function test_scan_callback_rejects_invalid_signature()
    {
        [$project, $evidence] = $this->makeProjectAndEvidence();

        $response = $this->postJson('/api/n8n/scan-callback', [
            'evidence_file_id' => $evidence->id,
            'scan_status' => 'clean',
        ]);

        $response->assertUnauthorized();
    }

    public function test_dashboard_scan_stats_endpoint_returns_totals()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'Admin']);
        $user->roles()->attach($role);

        [$project, $cleanEvidence] = $this->makeProjectAndEvidence();
        [, $infectedEvidence] = $this->makeProjectAndEvidence();

        // Clean scan
        app(EvidenceScanService::class)->recordScan($cleanEvidence, 'clean', ['infected' => false]);

        // Infected scan (quarantine)
        app(EvidenceScanService::class)->quarantine($infectedEvidence, 'Trojan.Test');

        $response = $this->actingAs($user)->getJson('/dashboard/scan-stats');

        $response->assertOk();
        $response->assertJsonPath('stats.total_scanned', 2);
        $response->assertJsonPath('stats.clean', 1);
        $response->assertJsonPath('stats.infected', 1);
        $response->assertJsonPath('stats.quarantined', 1);

        $this->assertCount(1, $response->json('recent_quarantined'));
        $this->assertEquals('Trojan.Test', $response->json('recent_quarantined.0.virus_name'));
    }
}
