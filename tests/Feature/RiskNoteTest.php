<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskScenario;
use App\Modules\RiskManagement\Models\RiskNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskNoteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected RiskRegister $risk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::create([
            'name' => 'Note Test Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);

        $this->risk = RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'NT-001',
            'asset_process_service' => 'Note Test',
            'risk_owner' => 'Owner',
            'risk_calculation_date' => now(),
            'asset_value_bdt' => 100000,
            'category' => 'Test',
            'department' => 'IT',
            'threats' => ['Threat'],
            'threat_level_t' => 3,
            'vulnerabilities' => ['Vuln'],
            'vulnerability_level_av' => 3,
            'impact_confidentiality' => 3,
            'impact_integrity' => 3,
            'impact_availability' => 3,
            'existing_control' => 'None',
            'tv_t_av' => 6,
            'likelihood_lh' => 3,
            'risk_rating_avtvlh' => 54,
            'measurement' => 'Not Accepted',
            'residual_tv' => 2,
            'residual_lh' => 2,
            'residual_rating' => 4,
        ]);
    }

    public function test_can_add_note_to_risk(): void
    {
        $this->actingAs($this->user);

        $note = RiskNote::create([
            'user_id' => $this->user->id,
            'notable_id' => $this->risk->id,
            'notable_type' => RiskRegister::class,
            'content' => 'Initial risk assessment completed.',
            'type' => 'comment',
        ]);

        $this->assertDatabaseHas('risk_notes', [
            'id' => $note->id,
            'notable_id' => $this->risk->id,
            'notable_type' => RiskRegister::class,
            'content' => 'Initial risk assessment completed.',
        ]);
    }

    public function test_risk_has_notes_relationship(): void
    {
        RiskNote::create([
            'user_id' => $this->user->id,
            'notable_id' => $this->risk->id,
            'notable_type' => RiskRegister::class,
            'content' => 'Note 1',
            'type' => 'comment',
        ]);

        RiskNote::create([
            'user_id' => $this->user->id,
            'notable_id' => $this->risk->id,
            'notable_type' => RiskRegister::class,
            'content' => 'Note 2',
            'type' => 'evidence',
        ]);

        $this->assertCount(2, $this->risk->notes);
    }

    public function test_can_add_note_to_scenario(): void
    {
        $scenario = RiskScenario::create([
            'title' => 'Scenario for Notes',
            'description' => 'Test scenario',
        ]);

        $note = RiskNote::create([
            'user_id' => $this->user->id,
            'notable_id' => $scenario->id,
            'notable_type' => RiskScenario::class,
            'content' => 'Scenario note content.',
            'type' => 'comment',
        ]);

        $this->assertDatabaseHas('risk_notes', [
            'id' => $note->id,
            'notable_id' => $scenario->id,
            'notable_type' => RiskScenario::class,
        ]);
    }

    public function test_note_has_user(): void
    {
        $note = RiskNote::create([
            'user_id' => $this->user->id,
            'notable_id' => $this->risk->id,
            'notable_type' => RiskRegister::class,
            'content' => 'User note test.',
            'type' => 'comment',
        ]);

        $this->assertNotNull($note->user);
        $this->assertEquals($this->user->id, $note->user->id);
    }

    public function test_note_can_be_attachment_type(): void
    {
        $note = RiskNote::create([
            'user_id' => $this->user->id,
            'notable_id' => $this->risk->id,
            'notable_type' => RiskRegister::class,
            'content' => 'Attachment reference',
            'type' => 'attachment',
        ]);

        $this->assertEquals('attachment', $note->type);
    }

    public function test_note_can_be_evidence_type(): void
    {
        $note = RiskNote::create([
            'user_id' => $this->user->id,
            'notable_id' => $this->risk->id,
            'notable_type' => RiskRegister::class,
            'content' => 'Evidence of control implementation',
            'type' => 'evidence',
        ]);

        $this->assertEquals('evidence', $note->type);
    }

    public function test_morph_many_relationship_on_notable(): void
    {
        $note = RiskNote::create([
            'user_id' => $this->user->id,
            'notable_id' => $this->risk->id,
            'notable_type' => RiskRegister::class,
            'content' => 'Morph test',
            'type' => 'comment',
        ]);

        $retrievedNote = RiskRegister::find($this->risk->id)->notes()->first();

        $this->assertEquals($note->id, $retrievedNote->id);
        $this->assertEquals('Morph test', $retrievedNote->content);
    }
}
