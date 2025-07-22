<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PciDssController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EvidenceController; // Make sure this is present

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

    // PCI DSS Module Routes
    Route::get('/pci/{project}', [PciDssController::class, 'show'])->name('pci.show');
    // New route for storing new PCI DSS project details
    Route::post('/pci', [PciDssController::class, 'store'])->name('pci.store');
    // Modified route to allow both PUT and POST for updating PCI DSS project details
    Route::match(['put', 'post'], '/pci/{project}', [PciDssController::class, 'update'])->name('pci.update');

    // Report Generation Route
    Route::get('/reports/pci/{project}', [ReportController::class, 'generate'])->name('reports.pci.generate');

    // User Management Routes (Admin/Auditor specific)
    Route::middleware(['can:is-admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Evidence Management Routes
    // Route for the main Evidence Hub page for a project
    Route::get('/projects/{project}/evidence', [EvidenceController::class, 'show'])->name('evidence.show');
    // Route for uploading files
    Route::post('/projects/{project}/evidence/upload', [EvidenceController::class, 'upload'])->name('evidence.upload');
    // Route for deleting evidence (if needed, add a destroy method to controller)
    // Route::delete('/evidence/{evidenceFile}', [EvidenceController::class, 'destroy'])->name('evidence.destroy');

    // API endpoints for chat (polled by frontend)
    Route::get('/projects/{project}/chat/messages', [EvidenceController::class, 'getMessages'])->name('chat.messages');
    Route::post('/projects/{project}/chat/messages', [EvidenceController::class, 'postMessage'])->name('chat.postMessage');

    // API endpoints for AI analysis approval (Auditor specific)
    Route::middleware(['can:is-auditor'])->group(function () {
        Route::post('/evidence/{evidenceFile}/approve-ai', [EvidenceController::class, 'approveAiAnalysis'])->name('evidence.approveAiAnalysis');
        Route::post('/evidence/{evidenceFile}/reject-ai', [EvidenceController::class, 'rejectAiAnalysis'])->name('evidence.rejectAiAnalysis');
    });
});

// n8n Callback Webhooks (No 'auth' middleware, as n8n is an external system)
// Secure these with a shared secret or IP whitelisting in production if possible.
Route::post('/n8n/file-scan-callback', [EvidenceController::class, 'n8nFileScanCallback'])->name('n8n.fileScanCallback');
Route::post('/n8n/ai-analysis-callback', [EvidenceController::class, 'n8nAiAnalysisCallback'])->name('n8n.aiAnalysisCallback');
// Route for n8n to fetch unread messages (can be secured via API key or IP whitelist)
Route::get('/n8n/get-unread-messages', [EvidenceController::class, 'getUnreadMessages'])->name('n8n.getUnreadMessages');

// Fallback route for root
Route::get('/', function () {
    return redirect()->route('dashboard');
});
