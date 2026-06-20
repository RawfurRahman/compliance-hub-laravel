<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PciDssController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EvidenceController;

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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/compliance-data', [DashboardController::class, 'submitComplianceData'])->name('compliance.submit');

    // Project Management Routes
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');

    // Project Hub & Module Routes
    Route::get('/projects/{project}',                  [\App\Http\Controllers\ProjectHubController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/scope',            [\App\Http\Controllers\ProjectHubController::class, 'scope'])->name('projects.scope');
    Route::post('/projects/{project}/scope',           [\App\Http\Controllers\ProjectHubController::class, 'scopeUpdate'])->name('projects.scope.update');
    Route::get('/projects/{project}/gap-assessment',   [\App\Http\Controllers\ProjectHubController::class, 'gapAssessment'])->name('projects.gap-assessment');
    Route::get('/projects/{project}/reporting',        [\App\Http\Controllers\ProjectHubController::class, 'reporting'])->name('projects.reporting');
    Route::get('/projects/{project}/reporting/{type}', [\App\Http\Controllers\ProjectHubController::class, 'report'])->name('projects.report');

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

    // Chat Message Routes (used by Evidence Hub real-time chat)
    Route::get('/projects/{project}/chat/messages', [EvidenceController::class, 'getMessages'])->name('evidence.chat.get');
    Route::post('/projects/{project}/chat/messages', [EvidenceController::class, 'postMessage'])->name('evidence.chat.post');

    // Meeting Routes
    Route::get('/projects/{project}/meetings', [\App\Http\Controllers\MeetingController::class, 'index'])->name('meetings.index');
    Route::post('/projects/{project}/meetings', [\App\Http\Controllers\MeetingController::class, 'store'])->name('meetings.store');
    Route::put('/projects/{project}/meetings/{meeting}/status', [\App\Http\Controllers\MeetingController::class, 'updateStatus'])->name('meetings.updateStatus');

    // Customer Team Routes
    Route::get('/team', [\App\Http\Controllers\CustomerTeamController::class, 'index'])->name('team.index');
    Route::post('/team', [\App\Http\Controllers\CustomerTeamController::class, 'store'])->name('team.store');
    Route::delete('/team/{team}', [\App\Http\Controllers\CustomerTeamController::class, 'destroy'])->name('team.destroy');

    // Report Generation Route
    Route::get('/reports/pci/{project}', [ReportController::class, 'generate'])->name('reports.pci.generate');

    // ISO 27001:2022 Gap Assessment Routes (legacy import-based)
    Route::get('/iso-gap/{project_id}',          [\App\Http\Controllers\IsoGapAssessmentController::class, 'index'])->name('iso-gap.index');
    Route::post('/iso-gap/{project_id}/import',  [\App\Http\Controllers\IsoGapAssessmentController::class, 'import'])->name('iso-gap.import');
    Route::post('/iso-gap/status/{id}',          [\App\Http\Controllers\IsoGapAssessmentController::class, 'updateStatus'])->name('iso-gap.update-status');
    Route::get('/iso-gap/{project_id}/report',   [\App\Http\Controllers\IsoGapAssessmentController::class, 'generateReport'])->name('iso-gap.report');

    // ── Unified Assessment Module ──────────────────────────────────────────
    Route::prefix('assessments')->name('assessments.')->group(function () {
        // Dashboard (with ?type=gap|final)
        Route::get('/{project}',                    [\App\Http\Controllers\AssessmentController::class, 'show'])->name('show');
        // Initialise a new assessment
        Route::post('/{project}',                   [\App\Http\Controllers\AssessmentController::class, 'store'])->name('store');
        // Clone gap → final
        Route::post('/{project}/clone',             [\App\Http\Controllers\AssessmentController::class, 'clone'])->name('clone');
        // Findings CRUD (JSON)
        Route::post('/assessment/{assessment}/findings',        [\App\Http\Controllers\AssessmentController::class, 'storeFinding'])->name('findings.store');
        Route::put('/findings/{finding}',                       [\App\Http\Controllers\AssessmentController::class, 'updateFinding'])->name('findings.update');
        Route::delete('/findings/{finding}',                    [\App\Http\Controllers\AssessmentController::class, 'destroyFinding'])->name('findings.destroy');
        // PDF report
        Route::get('/report/{assessment}',          [\App\Http\Controllers\AssessmentController::class, 'report'])->name('report');
    });

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

// n8n Webhook Callback Routes (Moved to api.php)

