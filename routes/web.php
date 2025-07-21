<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PciDssController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\EvidenceController; // Import the new controller

// Public routes
Route::get('/', function () { return view('welcome'); });
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login']);
Route::get('otp/verify', [AuthController::class, 'showOtpForm'])->name('otp.show');
Route::post('otp/verify', [AuthController::class, 'verifyOtp'])->name('otp.verify');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');

    // PCI DSS Module
    Route::get('/pci/{project}', [PciDssController::class, 'show'])->name('pci.show');
    Route::put('/pci/{project}', [PciDssController::class, 'update'])->name('pci.update');
    Route::post('/pci', [PciDssController::class, 'store'])->name('pci.store');

    // Report Generation
    Route::get('/pci/{project}/report', [ReportController::class, 'generate'])->name('pci.report');

    // ** NEW ROUTES FOR EVIDENCE MANAGEMENT **
    Route::prefix('evidence/{project}')->name('evidence.')->group(function () {
        Route::get('/', [EvidenceController::class, 'show'])->name('show');
        Route::post('/upload', [EvidenceController::class, 'upload'])->name('upload');
        Route::get('/chat', [EvidenceController::class, 'getMessages'])->name('chat.get');
        Route::post('/chat', [EvidenceController::class, 'postMessage'])->name('chat.post');
    });

    // API for n8n (simplified for localhost, in production you'd use a more secure token)
    Route::get('/api/unread-messages', [EvidenceController::class, 'getUnreadMessages'])->name('api.unread.messages');

    // User Management (Admin only)
    Route::middleware('can:is-admin')->group(function () {
        Route::resource('users', UserController::class);
    });
});
