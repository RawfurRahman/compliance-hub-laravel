<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Compliance\Controllers\ComplianceDashboardController;
use App\Modules\Compliance\Controllers\ControlTestController;
use App\Modules\Compliance\Controllers\ComplianceFindingController;
use App\Modules\Compliance\Controllers\RemediationController;
use App\Modules\Compliance\Controllers\ComplianceSnapshotController;
use App\Modules\Compliance\Controllers\AuditFindingController;

Route::middleware(['web', 'auth'])->group(function () {

    Route::prefix('projects/{project}/compliance')->name('compliance.')->group(function () {
        Route::get('/', [ComplianceDashboardController::class, 'index'])->name('dashboard');

        Route::get('/tests', [ControlTestController::class, 'index'])->name('tests.index');
        Route::post('/tests', [ControlTestController::class, 'store'])->name('tests.store');
        Route::get('/tests/{test}', [ControlTestController::class, 'show'])->name('tests.show');
        Route::get('/tests/history/{control}', [ControlTestController::class, 'history'])->name('tests.history');

        Route::get('/findings', [ComplianceFindingController::class, 'index'])->name('findings.index');
        Route::post('/findings/{finding}/state', [ComplianceFindingController::class, 'updateState'])->name('findings.state');

        Route::get('/remediations', [RemediationController::class, 'index'])->name('remediations.index');
        Route::post('/remediations', [RemediationController::class, 'store'])->name('remediations.store');
        Route::get('/remediations/{plan}', [RemediationController::class, 'show'])->name('remediations.show');
        Route::post('/remediations/{plan}/close', [RemediationController::class, 'close'])->name('remediations.close');

        Route::get('/snapshots', [ComplianceSnapshotController::class, 'index'])->name('snapshots.index');
        Route::post('/snapshots', [ComplianceSnapshotController::class, 'store'])->name('snapshots.store');
        Route::get('/snapshots/{from}/compare/{to}', [ComplianceSnapshotController::class, 'compare'])->name('snapshots.compare');

        Route::get('/audit-findings', [AuditFindingController::class, 'index'])->name('audit-findings.index');
        Route::post('/audit-findings', [AuditFindingController::class, 'store'])->name('audit-findings.store');
        Route::get('/audit-findings/{finding}', [AuditFindingController::class, 'show'])->name('audit-findings.show');
        Route::put('/audit-findings/{finding}', [AuditFindingController::class, 'update'])->name('audit-findings.update');
        Route::post('/audit-findings/{finding}/close', [AuditFindingController::class, 'close'])->name('audit-findings.close');
    });

    // Mapping import (not project-scoped, admin-like)
    Route::post('/compliance/mappings/import', [\App\Modules\Compliance\Controllers\MappingImportController::class, 'import'])->name('compliance.mappings.import');
    Route::post('/compliance/mappings/preview', [\App\Modules\Compliance\Controllers\MappingImportController::class, 'preview'])->name('compliance.mappings.preview');

});
