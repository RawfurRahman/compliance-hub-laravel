<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PciDssController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\MySecurityTasksController;

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

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/otp-verify', [AuthController::class, 'showOtpForm'])->name('otp.show');
Route::post('/otp-verify', [AuthController::class, 'verifyOtp'])->name('otp.verify');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Routes
    Route::middleware(['auth'])->group(function () {
        Route::get('/my-security-tasks', [MySecurityTasksController::class, 'index'])->name('my-security-tasks');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/governance', [DashboardController::class, 'governance'])->name('dashboard.governance');

        // Dashboard Analytics API (consumed by dashboard)
        Route::get('/dashboard/kpis', [DashboardController::class, 'kpis'])->name('dashboard.kpis');
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
        Route::get('kpis',                    [\App\Http\Controllers\Api\DashboardApiController::class, 'kpis']);
        Route::get('heatmap',                 [\App\Http\Controllers\Api\DashboardApiController::class, 'heatmap']);
        Route::get('top-risks',              [\App\Http\Controllers\Api\DashboardApiController::class, 'topRisks']);
        Route::get('inherent-vs-residual',   [\App\Http\Controllers\Api\DashboardApiController::class, 'inherentVsResidual']);
        Route::get('control-effectiveness',  [\App\Http\Controllers\Api\DashboardApiController::class, 'controlEffectiveness']);
        Route::get('compliance-scorecard',   [\App\Http\Controllers\Api\DashboardApiController::class, 'complianceScorecard']);
        Route::get('audit-findings',         [\App\Http\Controllers\Api\DashboardApiController::class, 'auditFindingsSummary']);
        Route::get('issues-remediation-trends', [\App\Http\Controllers\Api\DashboardApiController::class, 'issuesAndRemediationTrends']);
        Route::get('issue-aging',            [\App\Http\Controllers\Api\DashboardApiController::class, 'issueAging']);
        Route::get('third-party-risk',       [\App\Http\Controllers\Api\DashboardApiController::class, 'thirdPartyRiskSummary']);
        Route::get('policy-governance',      [\App\Http\Controllers\Api\DashboardApiController::class, 'policyGovernanceSummary']);
        Route::get('ownership-matrix',       [\App\Http\Controllers\Api\DashboardApiController::class, 'ownershipAccountability']);
        Route::get('financial-exposure',     [\App\Http\Controllers\Api\DashboardApiController::class, 'financialExposureSnapshot']);
        Route::get('sla-breach-rate',        [\App\Http\Controllers\Api\DashboardApiController::class, 'slaBreachRate']);
        Route::get('tests-summary',          [\App\Http\Controllers\Api\DashboardApiController::class, 'testsSummary']);
        Route::get('trust-center-activity',  [\App\Http\Controllers\Api\DashboardApiController::class, 'trustCenterActivity']);
        Route::get('metric-history',         [\App\Http\Controllers\Api\DashboardApiController::class, 'metricHistory']);
        Route::get('user',                   [\App\Http\Controllers\Api\DashboardApiController::class, 'user']);
    });

    // Compliance Data Routes
    Route::post('/projects/{project}/compliance-data', [DashboardController::class, 'submitComplianceData'])->name('project.compliance.submit');

    // Project Management Routes
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');

    // Project Hub & Module Routes
    Route::get('/projects/{project}',                  [\App\Http\Controllers\ProjectHubController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/scope',            [\App\Http\Controllers\ProjectHubController::class, 'scope'])->name('projects.scope');
    Route::post('/projects/{project}/scope',           [\App\Http\Controllers\ProjectHubController::class, 'scopeUpdate'])->name('projects.scope.update');
    // Gap Assessment Routes
    Route::get('/projects/{project}/gap-assessment', [\App\Http\Controllers\GapAssessmentController::class, 'index'])->name('projects.gap-assessment');
    Route::get('/projects/{project}/gap-assessment/report', [\App\Http\Controllers\GapAssessmentController::class, 'report'])->name('gap-assessment.report');
    Route::post('/projects/{project}/gap-assessment/initialize/{framework}', [\App\Http\Controllers\GapAssessmentController::class, 'initialize'])->name('gap-assessment.initialize');
    Route::put('/projects/{project}/gap-assessment/findings/{finding}', [\App\Http\Controllers\GapAssessmentController::class, 'update'])->name('gap-assessment.update');
    Route::get('/projects/{project}/gap-assessment/findings/{finding}', [\App\Http\Controllers\GapAssessmentController::class, 'getFinding'])->name('gap-assessment.get-finding');

    // PCI Gap Assessment Routes
    Route::get('/pci-gap/{project}', [\App\Http\Controllers\PciGapAssessmentController::class, 'index'])->name('pci-gap.index');
    Route::post('/pci-gap/{project}/import', [\App\Http\Controllers\PciGapAssessmentController::class, 'import'])->name('pci-gap.import');
    Route::post('/pci-gap/update-row/{id}', [\App\Http\Controllers\PciGapAssessmentController::class, 'updateRow'])->name('pci-gap.update-row');

    // ISO Gap Assessment Routes
    Route::get('/iso-gap/{project_id}', [\App\Http\Controllers\IsoGapAssessmentController::class, 'index'])->name('iso-gap.index');
    Route::post('/iso-gap/{project_id}/import', [\App\Http\Controllers\IsoGapAssessmentController::class, 'import'])->name('iso-gap.import');
    Route::post('/iso-gap/update-status/{id}', [\App\Http\Controllers\IsoGapAssessmentController::class, 'updateStatus'])->name('iso-gap.update-status');
    Route::get('/iso-gap/{project_id}/report', [\App\Http\Controllers\IsoGapAssessmentController::class, 'generateReport'])->name('iso-gap.report');

    Route::get('/projects/{project}/reporting',        [\App\Http\Controllers\ProjectHubController::class, 'reporting'])->name('projects.reporting');
    Route::get('/projects/{project}/reporting/{type}', [\App\Http\Controllers\ProjectHubController::class, 'report'])->name('projects.report');
    Route::get('/projects/{project}/reports/{type}/download', [\App\Http\Controllers\ProjectHubController::class, 'downloadReport'])->name('projects.report.download');

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
    Route::post('/evidence/{evidenceFile}/trust-center-toggle', [EvidenceController::class, 'toggleTrustCenterListing'])->name('evidence.trust-center-toggle');

    // Chat Message Routes (used by Evidence Hub real-time chat)
    Route::get('/projects/{project}/chat/messages', [EvidenceController::class, 'getMessages'])->name('evidence.chat.get');
    Route::post('/projects/{project}/chat/messages', [EvidenceController::class, 'postMessage'])->name('evidence.chat.post');

    // Meeting Routes
    Route::get('/projects/{project}/meetings', [\App\Http\Controllers\MeetingController::class, 'index'])->name('meetings.index');
    Route::post('/projects/{project}/meetings', [\App\Http\Controllers\MeetingController::class, 'store'])->name('meetings.store');
    Route::put('/projects/{project}/meetings/{meeting}', [\App\Http\Controllers\MeetingController::class, 'update'])->name('meetings.update');
    Route::put('/projects/{project}/meetings/{meeting}/status', [\App\Http\Controllers\MeetingController::class, 'updateStatus'])->name('meetings.updateStatus');

    // Customer Team Routes
    Route::get('/team', [\App\Http\Controllers\CustomerTeamController::class, 'index'])->name('team.index');
    Route::post('/team', [\App\Http\Controllers\CustomerTeamController::class, 'store'])->name('team.store');
    Route::delete('/team/{team}', [\App\Http\Controllers\CustomerTeamController::class, 'destroy'])->name('team.destroy');



    // User Management Routes (Admin/Auditor specific)
    Route::middleware(['can:is-admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        
        // Framework Management
        Route::resource('admin/requirements', \App\Http\Controllers\Admin\PciDssRequirementController::class)->except(['show'])->names('admin.requirements');
        
        // Dynamic Frameworks Management
        Route::resource('admin/frameworks', \App\Http\Controllers\Admin\FrameworkController::class)->except(['show', 'create', 'edit'])->names('admin.frameworks');
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

