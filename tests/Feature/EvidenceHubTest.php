<?php

namespace Tests\Feature;

use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\PciDssRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_evidence_hub_requires_project_selection()
    {
        $user = User::factory()->create();

        // Access /evidence-hub without project parameter
        $response = $this->actingAs($user)->get('/evidence-hub');

        $response->assertStatus(200);
        $response->assertViewHas('project', null);
        $response->assertViewHas('evidenceFiles');
        $response->assertSee('No Project Selected');
        $response->assertSee('Select a compliance project');
    }

    public function test_evidence_hub_loads_pci_dss_project_details()
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'PCI DSS Project',
            'module_type' => 'pci_dss',
            'user_id' => $user->id,
        ]);

        $requirement = PciDssRequirement::create([
            'req_num' => '1.1.1',
            'req_description' => 'Establish network security controls.',
        ]);

        $evidence = $project->evidenceFiles()->create([
            'pci_dss_requirement_id' => $requirement->id,
            'user_id' => $user->id,
            'file_path' => 'evidence/test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
        ]);

        $response = $this->actingAs($user)->get("/evidence-hub/{$project->id}");

        $response->assertStatus(200);
        $response->assertViewHas('project');
        $response->assertViewHas('frameworkName', 'PCI DSS');
        $response->assertSee('Evidence Tracker - PCI DSS Assessment');
    }

    public function test_evidence_hub_loads_agnostic_framework_project_details()
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'ISO Project',
            'module_type' => 'iso_27001',
            'user_id' => $user->id,
        ]);

        $framework = Framework::create([
            'name' => 'ISO 27001:2022',
            'slug' => 'iso_27001',
            'is_active' => true,
        ]);

        $control = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.5.1',
            'domain' => 'Policies',
            'requirement_description' => 'Security policies description.',
        ]);

        $evidence = $project->evidenceFiles()->create([
            'framework_control_id' => $control->id,
            'user_id' => $user->id,
            'file_path' => 'evidence/test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
        ]);

        $response = $this->actingAs($user)->get("/evidence-hub/{$project->id}");

        $response->assertStatus(200);
        $response->assertViewHas('project');
        $response->assertViewHas('frameworkName', 'ISO 27001:2022');
        $response->assertSee('Evidence Tracker - ISO 27001:2022 Assessment');
    }

    public function test_framework_control_name_parsing()
    {
        $framework = Framework::create([
            'name' => 'SWIFT CSP',
            'slug' => 'swift_csp',
            'is_active' => true,
        ]);

        // 1. Test parsing description with colon
        $control1 = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => '1.1',
            'domain' => 'Environment Protection',
            'requirement_description' => 'Swift Environment Protection: The SWIFT secure zone must be logically segregated.',
        ]);

        $this->assertEquals('Swift Environment Protection', $control1->control_name);

        // 2. Test control ID prefix stripping
        $control2 = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.9.9',
            'domain' => 'Policies',
            'requirement_description' => 'A.9.9 - Information Security: Description here.',
        ]);

        $this->assertEquals('Information Security', $control2->control_name);

        // 3. Test fallback to hardcoded name
        $control3 = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.5.2',
            'domain' => 'Policies',
            'requirement_description' => 'Description without colon.',
        ]);

        $this->assertEquals('Information security roles and responsibilities', $control3->control_name);
    }

    public function test_pci_dss_scope_and_gap_routes()
    {
        $user = User::factory()->create();
        
        $project = Project::create([
            'name' => 'PCI DSS Project',
            'module_type' => 'pci_dss',
            'user_id' => $user->id,
        ]);

        // Access scope page
        $response = $this->actingAs($user)->get("/projects/{$project->id}/scope");
        $response->assertStatus(200);
        $response->assertViewIs('projects.scope');

        // Access pci-gap index page
        $response = $this->actingAs($user)->get("/pci-gap/{$project->id}");
        $response->assertStatus(200);
        $response->assertViewIs('pci-gap.index');
    }

    public function test_unified_reports_menu_and_pdf_generation()
    {
        $user = User::factory()->create();
        
        $project = Project::create([
            'name' => 'ISO Assessment Project',
            'module_type' => 'iso_27001',
            'user_id' => $user->id,
        ]);

        $framework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'version' => '2022',
            'is_active' => true,
        ]);

        // Access reports menu
        $response = $this->actingAs($user)->get("/projects/{$project->id}/reporting");
        $response->assertStatus(200);
        $response->assertSee('ISO 27001 Gap Assessment Report');
        $response->assertSee('ISO 27001 Final Assessment Report');

        // Create Gap Assessment
        $assessment = ProjectAssessment::create([
            'project_id' => $project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        // Generate report preview view
        $response = $this->actingAs($user)->get("/projects/{$project->id}/reporting/unified_gap");
        $response->assertStatus(200);
        $response->assertViewIs('assessments.report-pdf');

        // Download report PDF
        $response = $this->actingAs($user)->get("/projects/{$project->id}/reporting/unified_gap/download?format=pdf");
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_email_report_sharing_with_attachments()
    {
        \Illuminate\Support\Facades\Mail::fake();

        $user = User::factory()->create();
        
        $project = Project::create([
            'name' => 'ISO Assessment Project',
            'module_type' => 'iso_27001',
            'user_id' => $user->id,
        ]);

        $framework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'version' => '2022',
            'is_active' => true,
        ]);

        ProjectAssessment::create([
            'project_id' => $project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        $response = $this->actingAs($user)->post("/projects/{$project->id}/reporting/unified_gap/share", [
            'email' => 'test-recipient@example.com',
            'subject' => 'Monthly Report Audit',
            'message' => 'Please find the attached compliance gap report.',
            'formats' => ['pdf', 'html'],
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ComplianceReportMail::class, function ($mail) {
            return $mail->hasTo('test-recipient@example.com') && 
                   $mail->projectName === 'ISO Assessment Project' &&
                   $mail->reportLabel === 'ISO 27001 Gap Assessment Report';
        });
    }

    public function test_report_scheduling_and_command_dispatch()
    {
        $user = User::factory()->create();
        
        $project = Project::create([
            'name' => 'ISO Assessment Project',
            'module_type' => 'iso_27001',
            'user_id' => $user->id,
        ]);

        $framework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'version' => '2022',
            'is_active' => true,
        ]);

        ProjectAssessment::create([
            'project_id' => $project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        // Create weekly schedule via POST route
        $response = $this->actingAs($user)->post("/projects/{$project->id}/reporting/schedules", [
            'report_type' => 'unified_gap',
            'recipient_email' => 'weekly-notify@example.com',
            'frequency' => 'weekly',
            'format' => 'both',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $schedule = \App\Models\ReportSchedule::where('project_id', $project->id)->first();
        $this->assertNotNull($schedule);
        $this->assertEquals('weekly', $schedule->frequency);
        $this->assertEquals('weekly-notify@example.com', $schedule->recipient_email);
        $this->assertTrue($schedule->next_run_at->isFuture());

        // Update schedule to be due for processing
        $oldNextRun = $schedule->next_run_at;
        $schedule->update(['next_run_at' => now()->subDay()]);

        \Illuminate\Support\Facades\Mail::fake();

        // Run scheduler command
        $exitCode = \Illuminate\Support\Facades\Artisan::call('compliance:send-scheduled-reports');
        $this->assertEquals(0, $exitCode);

        // Assert mail was sent
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ComplianceReportMail::class, function ($mail) {
            return $mail->hasTo('weekly-notify@example.com') && 
                   $mail->projectName === 'ISO Assessment Project';
        });

        // Assert updated schedule times
        $freshSchedule = $schedule->fresh();
        $this->assertNotNull($freshSchedule->last_sent_at);
        $this->assertTrue($freshSchedule->next_run_at->isFuture());
        $this->assertTrue($freshSchedule->next_run_at->gt($schedule->next_run_at));


        // Test delete schedule
        $response = $this->actingAs($user)->delete("/projects/{$project->id}/reporting/schedules/{$schedule->id}");
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertNull(\App\Models\ReportSchedule::find($schedule->id));
    }

    public function test_custom_report_generation_with_filters_and_sections()
    {
        $user = User::factory()->create();
        
        $project = Project::create([
            'name' => 'ISO Assessment Project',
            'module_type' => 'iso_27001',
            'user_id' => $user->id,
        ]);

        $framework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'version' => '2022',
            'is_active' => true,
        ]);

        $control1 = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.5.1',
            'domain' => 'Policies',
            'requirement_description' => 'Information security policies description.',
        ]);

        $control2 = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.5.2',
            'domain' => 'Policies',
            'requirement_description' => 'Review of policies description.',
        ]);

        $assessment = ProjectAssessment::create([
            'project_id' => $project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        // Initialize findings
        app(\App\Services\AssessmentService::class)->initialize($assessment);

        // Update finding 1 to be compliant, Low risk
        $finding1 = $assessment->findings()->where('framework_control_id', $control1->id)->first();
        $finding1->update([
            'is_compliant' => true,
            'risk_rating' => 'Low',
            'status' => 'Closed',
        ]);

        // Update finding 2 to be non-compliant, High risk
        $finding2 = $assessment->findings()->where('framework_control_id', $control2->id)->first();
        $finding2->update([
            'is_compliant' => false,
            'risk_rating' => 'High',
            'status' => 'Open',
        ]);

        // Test custom report preview with sections and filters
        // Let's filter by risk rating 'High' and status 'non_compliant' and exclude metrics section
        $response = $this->actingAs($user)->get("/projects/{$project->id}/reporting/unified_gap?" . http_build_query([
            'sections' => ['executive_summary', 'table', 'detailed_findings'], // Exclude metrics
            'filters' => [
                'status' => 'non_compliant',
                'risk' => 'High',
            ],
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('assessments.report-pdf');
        $response->assertSee('1. Executive Summary');
        $response->assertDontSee('Postures &amp; Metrics Breakdowns'); // Metrics should not render
        $response->assertSee($control2->control_id); // Finding 2 is High risk and non-compliant, so it should be visible
        $response->assertDontSee($control1->control_id); // Finding 1 is Low risk/compliant, should be filtered out
    }

    public function test_custom_report_template_storage_and_download()
    {
        $user = User::factory()->create();
        
        $project = Project::create([
            'name' => 'ISO Assessment Project',
            'module_type' => 'iso_27001',
            'user_id' => $user->id,
        ]);

        $framework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'version' => '2022',
            'is_active' => true,
        ]);

        $control = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.5.1',
            'domain' => 'Policies',
            'requirement_description' => 'Information security policies description.',
        ]);

        $assessment = ProjectAssessment::create([
            'project_id' => $project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        app(\App\Services\AssessmentService::class)->initialize($assessment);

        // Store custom report template
        $response = $this->actingAs($user)->post("/projects/{$project->id}/reporting/custom-templates", [
            'name' => 'Failures & High Risks Template',
            'report_type' => 'unified_gap',
            'sections' => ['executive_summary', 'table'],
            'filters' => [
                'status' => 'non_compliant',
                'risk' => 'High',
            ],
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $template = \App\Models\CustomReportTemplate::where('project_id', $project->id)->first();
        $this->assertNotNull($template);
        $this->assertEquals('Failures & High Risks Template', $template->name);
        $this->assertEquals('unified_gap', $template->report_type);
        $this->assertEquals(['executive_summary', 'table'], $template->sections);
        $this->assertEquals(['status' => 'non_compliant', 'risk' => 'High'], $template->filters);

        // Download report using custom template
        $response = $this->actingAs($user)->get("/projects/{$project->id}/reporting/custom-templates/{$template->id}/download?format=pdf");
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');

        // Delete custom template
        $response = $this->actingAs($user)->delete("/projects/{$project->id}/reporting/custom-templates/{$template->id}");
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertNull(\App\Models\CustomReportTemplate::find($template->id));
    }

    public function test_reporting_dashboard_metrics_calculation()
    {
        $user = User::factory()->create();
        
        $project = Project::create([
            'name' => 'ISO Assessment Project',
            'module_type' => 'iso_27001',
            'user_id' => $user->id,
        ]);

        $framework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'version' => '2022',
            'is_active' => true,
        ]);

        $control = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.5.1',
            'domain' => 'Policies',
            'requirement_description' => 'Information security policies description.',
        ]);

        $assessment = ProjectAssessment::create([
            'project_id' => $project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        // Initialize findings
        app(\App\Services\AssessmentService::class)->initialize($assessment);

        // Make finding compliant (100% compliance)
        $finding = $assessment->findings()->first();
        $finding->update([
            'is_compliant' => true,
            'risk_rating' => 'Low',
            'status' => 'Closed',
        ]);

        // Generate report 1 (which logs it with 100% compliance score snapshot)
        $response = $this->actingAs($user)->get("/projects/{$project->id}/reporting/unified_gap");
        $response->assertStatus(200);

        // Update finding to be non-compliant (0% compliance)
        $finding->update([
            'is_compliant' => false,
            'risk_rating' => 'High',
            'status' => 'Open',
        ]);

        // Generate report 2 (logs it with 0% compliance score snapshot)
        $response = $this->actingAs($user)->get("/projects/{$project->id}/reporting/unified_gap");
        $response->assertStatus(200);

        // Fetch reporting dashboard overview menu
        $response = $this->actingAs($user)->get("/projects/{$project->id}/reporting");
        $response->assertStatus(200);

        // Assert stats in view match
        $response->assertViewHas('totalReportsCount', 2);
        $response->assertViewHas('currentCompliance', 0); // Active assessment is now 0%

        // Assert trend dataset and snapshots are correctly compiled
        $trendData = $response->viewData('trendData');
        $this->assertEquals(2, $trendData->count());
        $this->assertEquals(100.0, $trendData[0]['value']);
        $this->assertEquals(0.0, $trendData[1]['value']);

        // Assert DOM content
        $response->assertSee('Reporting Analytics');
        $response->assertSee('Metrics');
        $response->assertSee('Compliance Score Trend Timeline');
        $response->assertSee('Report Distribution Insights');
        $response->assertSee('Total Reports');
    }
}


