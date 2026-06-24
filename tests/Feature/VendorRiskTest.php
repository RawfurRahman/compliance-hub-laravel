<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\RiskManagement\Models\ThirdPartyVendor;
use App\Modules\RiskManagement\Models\VendorAssessment;
use App\Modules\RiskManagement\Models\VendorQuestionnaireResponse;
use App\Modules\RiskManagement\Services\ThirdPartyVendorService;
use App\Modules\RiskManagement\Services\VendorAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorRiskTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::create([
            'name' => 'Vendor Risk Test Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_create_vendor(): void
    {
        $this->actingAs($this->user);
        $service = new ThirdPartyVendorService();

        $vendor = $service->create([
            'project_id' => $this->project->id,
            'vendor_name' => 'Acme Cloud Services',
            'service_category' => 'cloud_infrastructure',
            'contact_email' => 'vendor@acme.example',
        ]);

        $this->assertDatabaseHas('third_party_vendors', [
            'id' => $vendor->id,
            'vendor_name' => 'Acme Cloud Services',
        ]);
    }

    public function test_vendor_risk_tier_is_set(): void
    {
        $criticalVendor = ThirdPartyVendor::create([
            'project_id' => $this->project->id,
            'vendor_name' => 'Critical Vendor',
            'criticality' => 'critical',
            'data_classification' => 'restricted',
            'contact_email' => 'c@v.example',
        ]);

        $highVendor = ThirdPartyVendor::create([
            'project_id' => $this->project->id,
            'vendor_name' => 'High Vendor',
            'criticality' => 'high',
            'contact_email' => 'h@v.example',
        ]);

        $lowVendor = ThirdPartyVendor::create([
            'project_id' => $this->project->id,
            'vendor_name' => 'Low Vendor',
            'criticality' => 'low',
            'contact_email' => 'l@v.example',
        ]);

        $service = new ThirdPartyVendorService();

        $this->assertEquals('tier_1', $service->assessRiskTier($criticalVendor));
        $this->assertEquals('tier_2', $service->assessRiskTier($highVendor));
        $this->assertEquals('tier_3', $service->assessRiskTier($lowVendor));
    }

    public function test_can_create_assessment(): void
    {
        $vendor = ThirdPartyVendor::create([
            'project_id' => $this->project->id,
            'vendor_name' => 'Assessment Test Vendor',
            'service_category' => 'saas',
            'contact_email' => 'test@vendor.example',
        ]);

        $this->actingAs($this->user);
        $service = new VendorAssessmentService();

        $assessment = $service->create([
            'vendor_id' => $vendor->id,
            'assessor_id' => $this->user->id,
            'assessment_type' => 'initial',
        ]);

        $this->assertDatabaseHas('vendor_assessments', [
            'vendor_id' => $vendor->id,
            'assessment_type' => 'initial',
        ]);
    }

    public function test_can_submit_questionnaire_response(): void
    {
        $vendor = ThirdPartyVendor::create([
            'project_id' => $this->project->id,
            'vendor_name' => 'Response Test Vendor',
            'service_category' => 'saas',
            'contact_email' => 'resp@vendor.example',
        ]);

        $assessment = VendorAssessment::create([
            'vendor_id' => $vendor->id,
            'assessor_id' => $this->user->id,
            'assessment_type' => 'initial',
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->user);
        $service = new VendorAssessmentService();

        $response = $service->submitResponse($assessment, [
            'question_key' => 'encryption_at_rest',
            'question_text' => 'Does the vendor encrypt data at rest?',
            'response_text' => 'Yes',
            'score' => 10,
            'max_score' => 10,
        ]);

        $this->assertDatabaseHas('vendor_questionnaire_responses', [
            'vendor_assessment_id' => $assessment->id,
            'question_key' => 'encryption_at_rest',
            'score' => 10,
        ]);
    }

    public function test_can_complete_assessment(): void
    {
        $vendor = ThirdPartyVendor::create([
            'project_id' => $this->project->id,
            'vendor_name' => 'Complete Assessment Vendor',
            'service_category' => 'saas',
            'contact_email' => 'complete@vendor.example',
        ]);

        $assessment = VendorAssessment::create([
            'vendor_id' => $vendor->id,
            'assessor_id' => $this->user->id,
            'assessment_type' => 'initial',
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->user);
        $service = new VendorAssessmentService();

        $service->submitResponse($assessment, [
            'question_key' => 'sla',
            'question_text' => 'Is SLA in place?',
            'response_text' => 'Yes',
            'score' => 10,
            'max_score' => 10,
        ]);

        $completed = $service->completeAssessment($assessment);

        $this->assertEquals('completed', $completed->status);
        $this->assertNotNull($completed->overall_score);
        $this->assertNotNull($completed->completed_date);
    }

    public function test_score_to_rating(): void
    {
        $assessment = new VendorAssessment();
        $assessment->overall_score = 95;

        $this->assertEquals('Low', $assessment->scoreToRating(95));
        $this->assertEquals('Medium', $assessment->scoreToRating(75));
        $this->assertEquals('High', $assessment->scoreToRating(55));
        $this->assertEquals('Critical', $assessment->scoreToRating(25));
    }

    public function test_vendor_critical_scope(): void
    {
        ThirdPartyVendor::create([
            'project_id' => $this->project->id,
            'vendor_name' => 'Critical Vendor',
            'criticality' => 'critical',
            'contact_email' => 'critical@vendor.example',
        ]);

        ThirdPartyVendor::create([
            'project_id' => $this->project->id,
            'vendor_name' => 'Low Risk Vendor',
            'criticality' => 'low',
            'contact_email' => 'low@vendor.example',
        ]);

        $critical = ThirdPartyVendor::critical()->get();

        $this->assertCount(1, $critical);
        $this->assertEquals('Critical Vendor', $critical->first()->vendor_name);
    }

    public function test_assessment_is_overdue(): void
    {
        $vendor = ThirdPartyVendor::create([
            'project_id' => $this->project->id,
            'vendor_name' => 'Overdue Vendor',
            'service_category' => 'saas',
            'contact_email' => 'overdue@vendor.example',
        ]);

        $assessment = new VendorAssessment([
            'vendor_id' => $vendor->id,
            'due_date' => now()->subWeek(),
            'status' => 'in_progress',
        ]);

        $this->assertTrue($assessment->isOverdue);
    }

    public function test_assessment_is_not_overdue_when_completed(): void
    {
        $vendor = ThirdPartyVendor::create([
            'project_id' => $this->project->id,
            'vendor_name' => 'On Time Vendor',
            'service_category' => 'saas',
            'contact_email' => 'ontime@vendor.example',
        ]);

        $assessment = new VendorAssessment([
            'vendor_id' => $vendor->id,
            'due_date' => now()->subWeek(),
            'status' => 'completed',
        ]);

        $this->assertFalse($assessment->isOverdue);
    }
}
