<?php

namespace Tests\Feature;

use App\Models\IsoGapAssessment;
use App\Models\PciGapAssessment;
use App\Models\PciDssRequirement;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GapAssessmentEvidenceLinkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_attach_evidence_from_the_same_project_to_an_iso_gap_row()
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'ISO Project', 'module_type' => 'iso_27001', 'user_id' => $user->id]);

        $requirement = PciDssRequirement::create(['req_num' => '1.1', 'req_description' => 'desc']);
        $evidence = $project->evidenceFiles()->create([
            'pci_dss_requirement_id' => $requirement->id,
            'user_id' => $user->id,
            'file_path' => 'evidence/test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
        ]);

        $finding = IsoGapAssessment::create([
            'project_id' => $project->id,
            'serial_no' => '1',
            'clause_reference' => 'A.5.1',
            'risk_rating' => 'Medium',
            'status' => 'Open',
        ]);

        $response = $this->actingAs($user)->post("/iso-gap/{$finding->id}/attach-evidence", [
            'evidence_file_id' => $evidence->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($evidence->id, $finding->fresh()->evidence_file_id);
    }

    public function test_cannot_attach_evidence_from_a_different_project_to_a_pci_gap_row()
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'PCI Project', 'module_type' => 'pci_dss', 'user_id' => $user->id]);
        $otherProject = Project::create(['name' => 'Other Project', 'module_type' => 'pci_dss', 'user_id' => $user->id]);

        $requirement = PciDssRequirement::create(['req_num' => '1.1', 'req_description' => 'desc']);
        $foreignEvidence = $otherProject->evidenceFiles()->create([
            'pci_dss_requirement_id' => $requirement->id,
            'user_id' => $user->id,
            'file_path' => 'evidence/foreign.txt',
            'original_filename' => 'foreign.txt',
            'mime_type' => 'text/plain',
        ]);

        $assessment = PciGapAssessment::create([
            'project_id' => $project->id,
            'requirement_text' => '1.1.1',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($user)->post("/pci-gap/{$assessment->id}/attach-evidence", [
            'evidence_file_id' => $foreignEvidence->id,
        ]);

        $response->assertStatus(422);
        $this->assertNull($assessment->fresh()->evidence_file_id);
    }

    public function test_can_clear_attached_evidence_by_sending_null()
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'PCI Project', 'module_type' => 'pci_dss', 'user_id' => $user->id]);

        $requirement = PciDssRequirement::create(['req_num' => '1.1', 'req_description' => 'desc']);
        $evidence = $project->evidenceFiles()->create([
            'pci_dss_requirement_id' => $requirement->id,
            'user_id' => $user->id,
            'file_path' => 'evidence/test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
        ]);

        $assessment = PciGapAssessment::create([
            'project_id' => $project->id,
            'requirement_text' => '1.1.1',
            'status' => 'Pending',
            'evidence_file_id' => $evidence->id,
        ]);

        $response = $this->actingAs($user)->post("/pci-gap/{$assessment->id}/attach-evidence", [
            'evidence_file_id' => null,
        ]);

        $response->assertStatus(200);
        $this->assertNull($assessment->fresh()->evidence_file_id);
    }
}
