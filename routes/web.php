<?php

use App\Http\Controllers\Admin\FrameworkControlController;
use App\Http\Controllers\Admin\FrameworkController;
use App\Http\Controllers\Admin\PciDssRequirementController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerTeamController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\IsoGapAssessmentController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\PciDssController;
use App\Http\Controllers\PciGapAssessmentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectHubController;
use App\Http\Controllers\RequiredDocumentController;
use App\Http\Controllers\UnifiedAssessmentController;
use App\Http\Controllers\ObservationController;
use App\Http\Controllers\UnifiedGapAssessmentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Root redirect to Login
Route::get('/', function () {
    return redirect()->route('login');
});

// robots.txt served through middleware so security headers apply
Route::get('/robots.txt', function () {
    return response("User-agent: *\nDisallow:\n", 200, ['Content-Type' => 'text/plain']);
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/otp-verify', [AuthController::class, 'showOtpForm'])->name('otp.show');
Route::post('/otp-verify', [AuthController::class, 'verifyOtp'])->name('otp.verify');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Routes
Route::middleware(['auth'])->group(function () {

    // Dashboard Analytics API (consumed by dashboard)
    Route::middleware(['can:view-dashboard'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/kpis', [DashboardController::class, 'kpis'])->name('dashboard.kpis');
        Route::get('/dashboard/scan-stats', [DashboardController::class, 'scanStats'])->name('dashboard.scan-stats');
        Route::get('/dashboard/heatmap', [DashboardController::class, 'heatmap'])->name('dashboard.heatmap');
        Route::get('/dashboard/top-risks', [DashboardController::class, 'topRisks'])->name('dashboard.top-risks');
        Route::get('/dashboard/maturity-score', [DashboardController::class, 'maturityScore'])->name('dashboard.maturity-score');
        Route::get('/dashboard/inherent-vs-residual', [DashboardController::class, 'inherentVsResidualByDept'])->name('dashboard.inherent-vs-residual');
        Route::get('/dashboard/control-effectiveness', [DashboardController::class, 'controlEffectiveness'])->name('dashboard.control-effectiveness');
        Route::get('/dashboard/compliance-scorecard', [DashboardController::class, 'complianceScorecard'])->name('dashboard.compliance-scorecard');
        Route::get('/dashboard/risk-by-department', [DashboardController::class, 'riskByDepartment'])->name('dashboard.risk-by-department');
        Route::get('/dashboard/issues-and-remediation', [DashboardController::class, 'issuesAndRemediation'])->name('dashboard.issues-and-remediation');
        Route::get('/dashboard/risk-acceptance-split', [DashboardController::class, 'riskAcceptanceSplit'])->name('dashboard.risk-acceptance-split');

        // Dashboard JSON API — v1 (consumed by Vue dashboard frontend)
        Route::prefix('api/v1/dashboard')->name('dashboard-api.')->group(function () {
            Route::get('kpis', [DashboardApiController::class, 'kpis']);
            Route::get('heatmap', [DashboardApiController::class, 'heatmap']);
            Route::get('top-risks', [DashboardApiController::class, 'topRisks']);
            Route::get('inherent-vs-residual', [DashboardApiController::class, 'inherentVsResidual']);
            Route::get('control-effectiveness', [DashboardApiController::class, 'controlEffectiveness']);
            Route::get('compliance-scorecard', [DashboardApiController::class, 'complianceScorecard']);
            Route::get('audit-findings', [DashboardApiController::class, 'auditFindingsSummary']);
            Route::get('issues-remediation-trends', [DashboardApiController::class, 'issuesAndRemediationTrends']);
            Route::get('issue-aging', [DashboardApiController::class, 'issueAging']);
            Route::get('third-party-risk', [DashboardApiController::class, 'thirdPartyRiskSummary']);
            Route::get('financial-exposure', [DashboardApiController::class, 'financialExposureSnapshot']);
            Route::get('tests-summary', [DashboardApiController::class, 'testsSummary']);
            Route::get('metric-history', [DashboardApiController::class, 'metricHistory']);
            Route::get('user', [DashboardApiController::class, 'user']);
        });
    });

    // Compliance Data Routes
    Route::post('/projects/{project}/compliance-data', [DashboardController::class, 'submitComplianceData'])->name('project.compliance.submit');

    // Project Management Routes
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Project Hub & Module Routes
    Route::get('/projects/{project}', [ProjectHubController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/scope', [ProjectHubController::class, 'scope'])->name('projects.scope');
    Route::post('/projects/{project}/scope', [ProjectHubController::class, 'scopeUpdate'])->name('projects.scope.update');
    // Gap Assessment Routes (Unified - supports ISO 27001, PCI DSS, and other frameworks)
    Route::get('/projects/{project}/gap-assessment', [UnifiedGapAssessmentController::class, 'index'])->name('projects.gap-assessment');
    Route::get('/projects/{project}/gap-assessment/report', [UnifiedGapAssessmentController::class, 'report'])->name('gap-assessment.report');
    Route::get('/projects/{project}/gap-assessment/initialize/{framework}', [UnifiedGapAssessmentController::class, 'initialize'])->name('gap-assessment.initialize');
    Route::put('/projects/{project}/gap-assessment/findings/{finding}', [UnifiedGapAssessmentController::class, 'update'])->name('gap-assessment.update');
    Route::get('/projects/{project}/gap-assessment/findings/{finding}', [UnifiedGapAssessmentController::class, 'getFinding'])->name('gap-assessment.get-finding');

    // Observation Routes (raised from a Gap Assessment finding, optionally sent to the Final Assessment)
    Route::get('/projects/{project}/observations', [ObservationController::class, 'index'])->name('observations.index');
    Route::post('/gap-assessment/findings/{finding}/observations', [ObservationController::class, 'store'])->name('observations.store');
    Route::put('/observations/{observation}', [ObservationController::class, 'update'])->name('observations.update');
    Route::post('/observations/{observation}/send-to-final-assessment', [ObservationController::class, 'sendToFinalAssessment'])->name('observations.send-to-final-assessment');
    Route::post('/observations/{observation}/add-to-risk-register', [ObservationController::class, 'addToRiskRegister'])->name('observations.add-to-risk-register');

    // Legacy Assessment Routes (AssessmentController)
    Route::get('/assessments/{project}', [AssessmentController::class, 'show'])->name('assessments.show');
    Route::post('/assessments/{project}', [AssessmentController::class, 'store'])->name('assessments.store');
    Route::post('/assessments/{project}/clone', [AssessmentController::class, 'clone'])->name('assessments.clone');
    Route::get('/assessments/{assessment}/report', [AssessmentController::class, 'report'])->name('assessments.report');
    Route::post('/assessments/{assessment}/findings', [AssessmentController::class, 'storeFinding'])->name('assessments.findings.store');
    Route::put('/assessments/findings/{finding}', [AssessmentController::class, 'updateFinding'])->name('assessments.findings.update');
    Route::delete('/assessments/findings/{finding}', [AssessmentController::class, 'destroyFinding'])->name('assessments.findings.destroy');

    // Unified Assessment Routes
    Route::get('/projects/{project}/assessments/{framework_slug}/{type}', [UnifiedAssessmentController::class, 'show'])->name('assessments.unified.show');
    Route::post('/projects/{project}/assessments/{framework_slug}/{type}/initialize', [UnifiedAssessmentController::class, 'initialize'])->name('assessments.unified.initialize');
    Route::put('/assessments/findings/{finding}', [UnifiedAssessmentController::class, 'updateFinding'])->name('assessments.unified.update-finding');
    Route::post('/assessments/findings/{finding}/upload-evidence', [UnifiedAssessmentController::class, 'uploadEvidence'])->name('assessments.unified.upload-evidence');

    // PCI Gap Assessment Routes
    Route::get('/pci-gap/{project}', [PciGapAssessmentController::class, 'index'])->name('pci-gap.index');
    Route::post('/pci-gap/{project}/import', [PciGapAssessmentController::class, 'import'])->name('pci-gap.import');
    Route::post('/pci-gap/update-row/{id}', [PciGapAssessmentController::class, 'updateRow'])->name('pci-gap.update-row');
    Route::post('/pci-gap/{id}/attach-evidence', [PciGapAssessmentController::class, 'attachEvidence'])->name('pci-gap.attach-evidence');

    // ISO Gap Assessment Routes
    Route::get('/iso-gap/{project_id}', [IsoGapAssessmentController::class, 'index'])->name('iso-gap.index');
    Route::post('/iso-gap/{project_id}/import', [IsoGapAssessmentController::class, 'import'])->name('iso-gap.import');
    Route::post('/iso-gap/update-status/{id}', [IsoGapAssessmentController::class, 'updateStatus'])->name('iso-gap.update-status');
    Route::post('/iso-gap/{id}/attach-evidence', [IsoGapAssessmentController::class, 'attachEvidence'])->name('iso-gap.attach-evidence');
    Route::get('/iso-gap/{project_id}/report', [IsoGapAssessmentController::class, 'generateReport'])->name('iso-gap.report');

    Route::get('/projects/{project}/reporting', [ProjectHubController::class, 'reporting'])->name('projects.reporting');
    Route::get('/projects/{project}/reporting/{type}', [ProjectHubController::class, 'report'])->name('projects.report');
    Route::get('/projects/{project}/reporting/{type}/download', [ProjectHubController::class, 'downloadReport'])->name('projects.report.download');

    // Report Sharing Routes
    Route::post('/projects/{project}/reporting/unified_gap/share', [ProjectHubController::class, 'shareReport'])->name('projects.report.share');

    // Report Scheduling Routes
    Route::post('/projects/{project}/reporting/schedules', [ProjectHubController::class, 'scheduleReports'])->name('projects.report.schedules');
    Route::delete('/projects/{project}/reporting/schedules/{schedule}', [ProjectHubController::class, 'destroySchedule'])->name('projects.report.schedules.delete');

    // Custom Report Template Routes
    Route::post('/projects/{project}/reporting/custom-templates', [ProjectHubController::class, 'storeCustomTemplate'])->name('projects.custom-templates.store');
    Route::get('/projects/{project}/reporting/custom-templates/{template}/download', [ProjectHubController::class, 'downloadCustomTemplate'])->name('projects.custom-templates.download');
    Route::delete('/projects/{project}/reporting/custom-templates/{template}', [ProjectHubController::class, 'deleteCustomTemplate'])->name('projects.custom-templates.delete');

    // PCI DSS Module Routes
    Route::get('/pci/{project}', [PciDssController::class, 'show'])->name('pci.show');
    Route::match(['put', 'post'], '/pci/{project}', [PciDssController::class, 'update'])->name('pci.update');

    // Evidence Management & Operational Overhaul
    Route::get('/evidence-hub/{project?}', [EvidenceController::class, 'hub'])->name('evidence.hub');
    Route::get('/evidence/{project}', [EvidenceController::class, 'show'])->name('evidence.show');
    Route::post('/evidence/{project}/upload', [EvidenceController::class, 'upload'])->name('evidence.upload');
    Route::post('/evidence/{project}/{requirement}/assign', [EvidenceController::class, 'assignUser'])->name('evidence.assign');
    Route::get('/evidence/{project}/export-zip', [EvidenceController::class, 'exportZip'])->name('evidence.export-zip');
    Route::post('/evidence/{project}/{requirement}/toggle-scope', [EvidenceController::class, 'toggleScope'])->name('evidence.toggle-scope');
    Route::get('/evidence/{project}/activities', [EvidenceController::class, 'getLatestActivities'])->name('evidence.activities');
    Route::post('/evidence/{evidenceFile}/feedback', [EvidenceController::class, 'submitFeedback'])->name('evidence.submit-feedback');
    Route::get('/evidence/{evidenceFile}/feedbacks', [EvidenceController::class, 'getFeedbacks'])->name('evidence.get-feedbacks');
    Route::get('/evidence/{evidenceFile}/status', [EvidenceController::class, 'getStatus'])->name('evidence.get-status');
    Route::post('/evidence/{evidenceFile}/ai/approve', [EvidenceController::class, 'approveAiAnalysis'])->name('evidence.ai.approve');
    Route::post('/evidence/{evidenceFile}/ai/reject', [EvidenceController::class, 'rejectAiAnalysis'])->name('evidence.ai.reject');
    Route::post('/evidence/{evidenceFile}/consolidate-gaps', [EvidenceController::class, 'consolidateGaps'])->name('evidence.consolidate-gaps');
    Route::post('/evidence/{project}/bulk-consolidate-gaps', [EvidenceController::class, 'bulkConsolidateGaps'])->name('evidence.bulk-consolidate-gaps');

    // Evidence Tracker Workflow Routes
    Route::get('/evidence-tracker/{project}', [EvidenceController::class, 'trackerDashboard'])->name('evidence.tracker-dashboard');
    Route::post('/evidence/{evidenceFile}/submit-for-review', [EvidenceController::class, 'submitForReview'])->name('evidence.submit-for-review');
    Route::post('/evidence/{evidenceFile}/approve-with-data', [EvidenceController::class, 'approveWithData'])->name('evidence.approve-with-data');
    Route::post('/evidence/{evidenceFile}/review-and-send-to-gap-assessment', [EvidenceController::class, 'reviewAndSendToGapAssessment'])->name('evidence.review-and-send-to-gap-assessment');
    Route::post('/evidence/{evidenceFile}/send-to-gap-assessment', [EvidenceController::class, 'sendToGapAssessment'])->name('evidence.send-to-gap-assessment');
    Route::post('/evidence/{evidenceFile}/pass-to-final-report', [EvidenceController::class, 'passToFinalReport'])->name('evidence.pass-to-final-report');
    Route::post('/evidence/{evidenceFile}/pass-to-gap-assessment', [EvidenceController::class, 'passToGapAssessment'])->name('evidence.pass-to-gap-assessment');
    Route::post('/evidence/{evidenceFile}/auto-create-risk', [EvidenceController::class, 'autoCreateRisk'])->name('evidence.auto-create-risk');
    Route::post('/evidence/{evidenceFile}/reject-tracker-review', [EvidenceController::class, 'rejectTrackerReview'])->name('evidence.reject-tracker-review');
    Route::get('/evidence/{evidenceFile}/workflow-history', [EvidenceController::class, 'getWorkflowHistory'])->name('evidence.workflow-history');
    Route::get('/evidence/{evidenceFile}/analysis-versions', [EvidenceController::class, 'getAnalysisVersions'])->name('evidence.analysis-versions');
    Route::post('/evidence/{project}/bulk-send-to-gap-assessment', [EvidenceController::class, 'bulkSendToGapAssessment'])->name('evidence.bulk-send-to-gap');
    Route::post('/evidence/{project}/bulk-pass-to-final-report', [EvidenceController::class, 'bulkPassToFinalReport'])->name('evidence.bulk-pass-to-final');
    Route::post('/evidence/{project}/bulk-auto-create-risks', [EvidenceController::class, 'bulkAutoCreateRisks'])->name('evidence.bulk-create-risks');

    // Chat Message Routes (used by Evidence Hub real-time chat)
    Route::get('/projects/{project}/chat/messages', [EvidenceController::class, 'getMessages'])->name('evidence.chat.get');
    Route::post('/projects/{project}/chat/messages', [EvidenceController::class, 'postMessage'])->name('evidence.chat.post');

    // Required Document Routes
    Route::get('/projects/{project}/required-documents', [RequiredDocumentController::class, 'index'])->name('required-documents.index');
    Route::post('/projects/{project}/required-documents/import', [RequiredDocumentController::class, 'import'])->name('required-documents.import');
    Route::get('/projects/{project}/required-documents/{list}', [RequiredDocumentController::class, 'show'])->name('required-documents.show');
    Route::delete('/projects/{project}/required-documents/{list}', [RequiredDocumentController::class, 'destroy'])->name('required-documents.destroy');

    // Meeting Routes
    Route::get('/projects/{project}/meetings', [MeetingController::class, 'index'])->name('meetings.index');
    Route::post('/projects/{project}/meetings', [MeetingController::class, 'store'])->name('meetings.store');
    Route::put('/projects/{project}/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
    Route::put('/projects/{project}/meetings/{meeting}/status', [MeetingController::class, 'updateStatus'])->name('meetings.updateStatus');

    // Customer Team Routes
    Route::get('/team', [CustomerTeamController::class, 'index'])->name('team.index');
    Route::post('/team', [CustomerTeamController::class, 'store'])->name('team.store');
    Route::delete('/team/{team}', [CustomerTeamController::class, 'destroy'])->name('team.destroy');

    // User Management Routes (Admin/Auditor specific)
    Route::middleware(['can:is-admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Framework Management
        Route::resource('admin/requirements', PciDssRequirementController::class)->except(['show'])->names('admin.requirements');

        // Dynamic Frameworks Management
        Route::resource('admin/frameworks', FrameworkController::class)->except(['show', 'create', 'edit'])->names('admin.frameworks');
        Route::get('admin/frameworks/{framework}/controls', [FrameworkControlController::class, 'index'])->name('admin.frameworks.controls.index');
        Route::post('admin/frameworks/{framework}/controls', [FrameworkControlController::class, 'store'])->name('admin.frameworks.controls.store');
        Route::post('admin/frameworks/{framework}/controls/import', [FrameworkControlController::class, 'import'])->name('admin.frameworks.controls.import');
        Route::delete('admin/frameworks/{framework}/controls/{control}', [FrameworkControlController::class, 'destroy'])->name('admin.frameworks.controls.destroy');
    });
});

// Profile and Settings Routes (for authenticated users)
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/settings', [ProfileController::class, 'settings'])->name('profile.settings');
    Route::put('/settings', [ProfileController::class, 'updateSettings'])->name('profile.update-settings');
});

// n8n Webhook Callback Routes (Moved to api.php)

// ── Load-test auth bypass (OFF by default) ──────────────────────────
// When LOAD_TEST_BYPASS_ENABLED=true in .env, GET /auto-login logs in
// as the given user_id (default 1) and returns JSON.  This skips OTP
// entirely and is intended ONLY for automated load-testing with k6.
// The route returns 403 when the flag is absent or false.
Route::get('/auto-login', function () {
    if (! env('LOAD_TEST_BYPASS_ENABLED', false)) {
        abort(403, 'Load-test bypass is not enabled. Set LOAD_TEST_BYPASS_ENABLED=true in .env to use this route.');
    }

    $userId = (int) request('user_id', 1);
    Auth::loginUsingId($userId);

    return response()->json([
        'status' => 'ok',
        'user_id' => $userId,
        'message' => 'Session established. Use the session cookie for subsequent requests.',
    ]);
});
