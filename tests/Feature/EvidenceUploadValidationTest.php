<?php

namespace Tests\Feature;

use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Verifies the extension / MIME whitelist at the evidence upload boundary.
 *
 * The ClamAV scan is intentionally left untouched — this is an additional
 * defence layer, not a replacement.
 *
 * Content-sensitive cases use real UploadedFile instances because the test
 * fake reports the MIME type from the file name, which cannot detect a
 * .php file renamed to .pdf.
 */
class EvidenceUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    private int $controlId;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();

        $framework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'is_active' => true,
        ]);

        $this->controlId = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.5.1',
            'domain' => 'General',
            'requirement_description' => 'Test requirement',
        ])->id;

        $this->project = Project::create([
            'name' => 'Upload Whitelist Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);
    }

    private function realUpload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'evid_upload_');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    private function validPdf(): UploadedFile
    {
        return $this->realUpload(
            'evidence.pdf',
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF"
        );
    }

    /**
     * An allowed PDF passes the whitelist and is stored as evidence.
     */
    public function test_allowed_pdf_is_accepted(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user)->postJson(route('evidence.upload', $this->project), [
            'requirement_id' => $this->controlId,
            'file' => $this->validPdf(),
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('evidence_files', 1);
        $this->assertDatabaseHas('evidence_files', [
            'project_id' => $this->project->id,
            'original_filename' => 'evidence.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $stored = Storage::disk('public')->files('evidence/'.$this->project->id);
        $this->assertCount(1, $stored);
        $this->assertStringEndsWith('.pdf', $stored[0]);
    }

    /**
     * A .php file is rejected with a 422, a clear message, and an activity-log entry.
     */
    public function test_php_file_is_rejected_with_422(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('evidence.upload', $this->project), [
            'requirement_id' => $this->controlId,
            'file' => $this->realUpload('shell.php', '<?php echo "pwned"; ?>'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('evidence_files', 0);
        $this->assertDatabaseHas('activity_log', [
            'user_id' => $this->user->id,
            'action' => 'evidence.upload.rejected',
        ]);
    }

    /**
     * A .php file renamed to .pdf is rejected by the mimetypes rule because the
     * sniffed content type (text/x-php) does not agree with the .pdf extension.
     */
    public function test_php_renamed_to_pdf_is_rejected_by_mimetypes(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('evidence.upload', $this->project), [
            'requirement_id' => $this->controlId,
            'file' => $this->realUpload('innocent.pdf', '<?php echo "pwned"; ?>'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');

        $errors = (array) $response->json('errors.file');
        $this->assertStringContainsString('content does not match', implode(' ', $errors));

        $this->assertDatabaseCount('evidence_files', 0);
    }

    /**
     * A file larger than the 20 MB cap is rejected.
     */
    public function test_oversized_file_is_rejected(): void
    {
        $oversized = $this->realUpload(
            'big.pdf',
            "%PDF-1.4\n".str_repeat('0', 21 * 1024 * 1024)."\n%%EOF"
        );

        $response = $this->actingAs($this->user)->postJson(route('evidence.upload', $this->project), [
            'requirement_id' => $this->controlId,
            'file' => $oversized,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');

        $errors = (array) $response->json('errors.file');
        $this->assertStringContainsString('20 MB', implode(' ', $errors));

        $this->assertDatabaseCount('evidence_files', 0);
    }

    /**
     * A rejected upload creates no evidence_files row and stores nothing.
     */
    public function test_rejected_upload_creates_no_evidence_row(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('evidence.upload', $this->project), [
            'requirement_id' => $this->controlId,
            'file' => $this->realUpload('evil.php', '<?php echo "x"; ?>'),
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('evidence_files', 0);
        Storage::disk('public')->assertMissing('evidence/'.$this->project->id.'/evil.php');

        $this->assertDatabaseHas('activity_log', [
            'user_id' => $this->user->id,
            'action' => 'evidence.upload.rejected',
        ]);
    }
}
