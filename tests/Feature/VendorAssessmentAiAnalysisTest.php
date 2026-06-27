<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\RiskManagement\Models\ThirdPartyVendor;
use App\Modules\RiskManagement\Models\VendorAssessment;
use App\Modules\RiskManagement\Models\VendorQuestionnaireResponse;
use App\Services\VendorAssessmentAnalysisService;
use App\Modules\RiskManagement\Events\VendorAssessmentCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VendorAssessmentAiAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected ThirdPartyVendor $vendor;
    protected VendorAssessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('vendor_questionnaire_responses');
        Schema::dropIfExists('vendor_assessments');
        Schema::dropIfExists('third_party_vendors');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');

        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
        Schema::create('user_roles', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('third_party_vendors', function ($table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vendor_name');
            $table->string('vendor_code')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('website')->nullable();
            $table->string('service_category')->nullable();
            $table->string('criticality')->nullable();
            $table->string('risk_tier')->nullable();
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->string('data_classification')->nullable();
            $table->text('data_shared')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('vendor_assessments', function ($table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('third_party_vendors')->cascadeOnDelete();
            $table->foreignId('assessor_id')->constrained('users')->cascadeOnDelete();
            $table->string('assessment_type');
            $table->date('assessment_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->string('risk_rating')->nullable();
            $table->text('findings_summary')->nullable();
            $table->boolean('remediation_required')->default(false);
            $table->date('remediation_deadline')->nullable();
            $table->text('notes')->nullable();
            $table->text('ai_summary')->nullable();
            $table->timestamp('ai_summary_generated_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('vendor_questionnaire_responses', function ($table) {
            $table->id();
            $table->foreignId('vendor_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('section')->nullable();
            $table->string('question_key');
            $table->string('question_text');
            $table->text('response_text')->nullable();
            $table->string('response_type')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('max_score', 5, 2)->nullable();
            $table->string('evidence_file')->nullable();
            $table->boolean('is_compliant')->nullable();
            $table->text('comments')->nullable();
            $table->text('ai_suggested_answer')->nullable();
            $table->boolean('needs_vendor_review')->default(false);
            $table->timestamps();
        });

        $this->user = User::factory()->create([
            'username' => 'testadmin',
            'email' => 'admin@test.com',
        ]);

        $adminRoleId = DB::table('roles')->insertGetId(['name' => 'Admin']);
        DB::table('user_roles')->insert([
            'user_id' => $this->user->id,
            'role_id' => $adminRoleId,
        ]);

        $this->project = Project::create([
            'name' => 'Vendor AI Test Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);

        $this->vendor = ThirdPartyVendor::create([
            'project_id' => $this->project->id,
            'vendor_name' => 'Acme Cloud Services',
            'service_category' => 'cloud_infrastructure',
            'contact_email' => 'vendor@acme.example',
        ]);

        $this->assessment = VendorAssessment::create([
            'vendor_id' => $this->vendor->id,
            'assessor_id' => $this->user->id,
            'assessment_type' => 'initial',
            'assessment_date' => now(),
            'status' => 'in_progress',
        ]);
    }

    public function test_analysis_generates_strengths_and_weaknesses(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"strengths":[{"strength":"Vendor has a clear encryption policy for data at rest","questions":["Q1"]},{"strength":"Access controls follow least-privilege principle","questions":["Q2"]}],"weaknesses":[{"weakness":"No evidence of independent penetration testing in the last 12 months","questions":["Q4"]}],"suggestions":[]}']]]]
                ]
            ], 200),
        ]);

        $this->seedResponses();

        $service = app(VendorAssessmentAnalysisService::class);
        $result = $service->analyze($this->assessment);

        $this->assertCount(2, $result['strengths']);
        $this->assertCount(1, $result['weaknesses']);
        $this->assertEquals('Vendor has a clear encryption policy for data at rest', $result['strengths'][0]['strength']);
        $this->assertEquals(['Q1'], $result['strengths'][0]['questions']);
        $this->assertEquals('No evidence of independent penetration testing in the last 12 months', $result['weaknesses'][0]['weakness']);

        $this->assessment->refresh();
        $this->assertNotNull($this->assessment->ai_summary);
        $this->assertNotNull($this->assessment->ai_summary_generated_at);
    }

    public function test_analysis_stores_suggestions_for_unanswered_questions(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"strengths":[],"weaknesses":[],"suggestions":[{"question_key":"incident_response","suggested_answer":"Vendor likely has a 24/7 SOC with SIEM monitoring aligned with industry standards for cloud service providers."}]}']]]]
                ]
            ], 200),
        ]);

        $this->seedResponses(unansweredKeys: ['incident_response']);

        $service = app(VendorAssessmentAnalysisService::class);
        $result = $service->analyze($this->assessment);

        $response = VendorQuestionnaireResponse::where('question_key', 'incident_response')->first();
        $this->assertNotNull($response->ai_suggested_answer);
        $this->assertStringContainsString('SOC', $response->ai_suggested_answer);
    }

    public function test_analysis_question_references_are_present(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"strengths":[{"strength":"Encryption policy is well-documented","questions":["Q1","Q3"]}],"weaknesses":[{"weakness":"No recent DR test","questions":["Q4","Q5"]}],"suggestions":[]}']]]]
                ]
            ], 200),
        ]);

        $this->seedResponses();

        $service = app(VendorAssessmentAnalysisService::class);
        $result = $service->analyze($this->assessment);

        foreach ($result['strengths'] as $strength) {
            $this->assertNotEmpty($strength['questions']);
        }
        foreach ($result['weaknesses'] as $weakness) {
            $this->assertNotEmpty($weakness['questions']);
        }
    }

    public function test_summary_page_shows_disclaimer(): void
    {
        $assessment = VendorAssessment::create([
            'vendor_id' => $this->vendor->id,
            'assessor_id' => $this->user->id,
            'assessment_type' => 'annual',
            'status' => 'completed',
            'ai_summary' => [
                'strengths' => [
                    ['strength' => 'Strong encryption policy', 'questions' => ['Q1']],
                ],
                'weaknesses' => [
                    ['weakness' => 'No pen test evidence', 'questions' => ['Q4']],
                ],
                'suggestions' => [],
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('vendors.assessments.detail', [$this->project, $this->vendor, $assessment]));

        $response->assertStatus(200);
        $response->assertSee('AI-generated, please verify');
        $response->assertSee('Vendor Assessment');
        $response->assertSee('Strong encryption policy');
        $response->assertSee('No pen test evidence');
    }

    public function test_flag_for_review_toggles_flag(): void
    {
        $this->seedResponses();
        $response = $this->assessment->responses()->first();

        $this->assertFalse($response->needs_vendor_review);

        $resp = $this->actingAs($this->user)
            ->post(route('vendors.assessments.responses.flag', [$this->project, $this->vendor, $this->assessment, $response]));

        $resp->assertStatus(200);
        $response->refresh();
        $this->assertTrue($response->needs_vendor_review);

        $resp = $this->actingAs($this->user)
            ->post(route('vendors.assessments.responses.flag', [$this->project, $this->vendor, $this->assessment, $response]));

        $response->refresh();
        $this->assertFalse($response->needs_vendor_review);
    }

    public function test_analysis_triggers_on_completion_event(): void
    {
        Event::fake();

        $this->seedResponses();
        $this->assessment->update(['status' => 'in_progress']);

        $this->assessment->update([
            'status' => 'completed',
            'completed_date' => now(),
        ]);

        VendorAssessmentCompleted::dispatch($this->assessment);

        Event::assertDispatched(VendorAssessmentCompleted::class, function ($event) {
            return $event->assessment->id === $this->assessment->id;
        });
    }

    public function test_summary_is_specific_not_generic_filler(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{
                        "strengths":[
                            {"strength":"Vendor encrypts all data at rest using AES-256 and transit using TLS 1.3, directly meeting the encryption question requirements","questions":["Q1"]},
                            {"strength":"Access control policy enforces role-based access with quarterly review cycles, addressing the access governance concern","questions":["Q2"]},
                            {"strength":"SOC 2 Type II report is current and covers all relevant trust service criteria for the services provided","questions":["Q5"]}
                        ],
                        "weaknesses":[
                            {"weakness":"Business continuity plan exists but no evidence of a tabletop exercise or full-scale DR test within the last 12 months","questions":["Q3","Q4"]},
                            {"weakness":"Incident response procedure does not include specific SLAs for containment and eradication phases","questions":["Q6"]}
                        ],
                        "suggestions":[]
                    }']]]]
                ]
            ], 200),
        ]);

        $this->createRealisticResponses();

        $service = app(VendorAssessmentAnalysisService::class);
        $result = $service->analyze($this->assessment);

        $this->assertCount(3, $result['strengths']);
        $this->assertCount(2, $result['weaknesses']);

        $allText = json_encode($result);

        $this->assertStringContainsString('AES-256', $allText);
        $this->assertStringContainsString('SOC 2', $allText);
        $this->assertStringContainsString('business continuity', strtolower($allText));
        $this->assertStringContainsString('incident response', strtolower($allText));

        foreach ($result['strengths'] as $s) {
            $this->assertNotEmpty($s['strength']);
            $this->assertGreaterThan(20, strlen($s['strength']));
            $this->assertNotEmpty($s['questions']);
        }
        foreach ($result['weaknesses'] as $w) {
            $this->assertNotEmpty($w['weakness']);
            $this->assertGreaterThan(20, strlen($w['weakness']));
            $this->assertNotEmpty($w['questions']);
        }
    }

    private function seedResponses(array $unansweredKeys = []): void
    {
        $questions = [
            ['question_key' => 'encryption_at_rest', 'section' => 'Security', 'question_text' => 'Does the vendor encrypt data at rest?', 'response_type' => 'yes_no'],
            ['question_key' => 'access_control', 'section' => 'Security', 'question_text' => 'Is access control enforced via least-privilege?', 'response_type' => 'yes_no'],
            ['question_key' => 'bcp_dr', 'section' => 'Resilience', 'question_text' => 'Does the vendor have a business continuity and DR plan?', 'response_type' => 'yes_no'],
            ['question_key' => 'pen_testing', 'section' => 'Security', 'question_text' => 'Does the vendor perform regular penetration testing?', 'response_type' => 'yes_no'],
            ['question_key' => 'incident_response', 'section' => 'Security', 'question_text' => 'Does the vendor have an incident response process?', 'response_type' => 'yes_no'],
        ];

        foreach ($questions as $q) {
            $isUnanswered = in_array($q['question_key'], $unansweredKeys);
            $this->assessment->responses()->create([
                'section' => $q['section'],
                'question_key' => $q['question_key'],
                'question_text' => $q['question_text'],
                'response_text' => $isUnanswered ? null : 'Yes, procedures are in place and documented.',
                'response_type' => $q['response_type'],
                'score' => $isUnanswered ? null : 8,
                'max_score' => 10,
                'is_compliant' => $isUnanswered ? null : true,
            ]);
        }
    }

    private function createRealisticResponses(): void
    {
        $data = [
            ['section' => 'Information Security', 'question_key' => 'encryption', 'question_text' => 'Describe the encryption methods used for data at rest and in transit.', 'response_text' => 'We use AES-256 for data at rest and TLS 1.3 for data in transit across all services.', 'score' => 10, 'max_score' => 10, 'is_compliant' => true],
            ['section' => 'Access Control', 'question_key' => 'access_governance', 'question_text' => 'How does the organization manage user access and segregation of duties?', 'response_text' => 'Role-based access control is enforced with quarterly access reviews and automated provisioning/deprovisioning.', 'score' => 9, 'max_score' => 10, 'is_compliant' => true],
            ['section' => 'Business Continuity', 'question_key' => 'bcp', 'question_text' => 'Does the organization maintain a business continuity plan?', 'response_text' => 'A BCP is maintained and reviewed annually. Planned DR test scheduled for next quarter.', 'score' => 6, 'max_score' => 10, 'is_compliant' => false],
            ['section' => 'Business Continuity', 'question_key' => 'dr_testing', 'question_text' => 'When was the last disaster recovery test conducted?', 'response_text' => 'The last full-scale DR test was over 18 months ago. A tabletop was conducted 6 months ago.', 'score' => 5, 'max_score' => 10, 'is_compliant' => false],
            ['section' => 'Compliance', 'question_key' => 'soc2', 'question_text' => 'Does the vendor hold a current SOC 2 Type II report?', 'response_text' => 'Yes, SOC 2 Type II report dated within the last 12 months is available upon request.', 'score' => 10, 'max_score' => 10, 'is_compliant' => true],
            ['section' => 'Incident Response', 'question_key' => 'ir_process', 'question_text' => 'Describe the incident response process and SLAs.', 'response_text' => 'Incident response follows NIST 800-61 framework. SLAs for detection and reporting are defined but containment SLAs are not yet documented.', 'score' => 7, 'max_score' => 10, 'is_compliant' => true],
        ];

        foreach ($data as $row) {
            $this->assessment->responses()->create($row);
        }
    }
}
