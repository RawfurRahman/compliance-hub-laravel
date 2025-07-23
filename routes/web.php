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
    Route::post('/pci', [PciDssController::class, 'store'])->name('pci.store');
    Route::match(['put', 'post'], '/pci/{project}', [PciDssController::class, 'update'])->name('pci.update');

    // Evidence Routes
    Route::get('/evidence/{project}', [EvidenceController::class, 'show'])->name('evidence.show');
    Route::post('/evidence/{project}/upload', [EvidenceController::class, 'upload'])->name('evidence.upload');

    // Report Generation Route
    Route::get('/reports/pci/{project}', [ReportController::class, 'generate'])->name('reports.pci.generate');

    // User Management Routes (Admin/Auditor specific)
    Route::middleware(['can:is-admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
