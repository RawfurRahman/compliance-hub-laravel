<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Compliance\Controllers\ControlTestController;
use App\Modules\Compliance\Controllers\ComplianceFindingController;
use App\Modules\Compliance\Controllers\RemediationController;
use App\Modules\Compliance\Controllers\ComplianceSnapshotController;
use App\Modules\Compliance\Controllers\AuditFindingController;
use App\Modules\Compliance\Controllers\MappingImportController;

Route::middleware('api')->prefix('api')->group(function () {

    Route::middleware('auth:sanctum')->prefix('compliance')->name('compliance.')->group(function () {

        Route::post('/tests', [ControlTestController::class, 'store'])->name('api.tests.store');
        Route::get('/tests/history/{control}', [ControlTestController::class, 'history'])->name('api.tests.history');

        Route::get('/findings', [ComplianceFindingController::class, 'index'])->name('api.findings.index');
        Route::post('/findings/{finding}/state', [ComplianceFindingController::class, 'updateState'])->name('api.findings.state');

        Route::get('/remediations', [RemediationController::class, 'index'])->name('api.remediations.index');
        Route::post('/remediations', [RemediationController::class, 'store'])->name('api.remediations.store');
        Route::post('/remediations/{plan}/close', [RemediationController::class, 'close'])->name('api.remediations.close');

        Route::post('/snapshots', [ComplianceSnapshotController::class, 'store'])->name('api.snapshots.store');
        Route::get('/snapshots/{from}/compare/{to}', [ComplianceSnapshotController::class, 'compare'])->name('api.snapshots.compare');

        Route::get('/audit-findings', [AuditFindingController::class, 'index'])->name('api.audit-findings.index');
        Route::post('/audit-findings', [AuditFindingController::class, 'store'])->name('api.audit-findings.store');
        Route::put('/audit-findings/{finding}', [AuditFindingController::class, 'update'])->name('api.audit-findings.update');
        Route::post('/audit-findings/{finding}/close', [AuditFindingController::class, 'close'])->name('api.audit-findings.close');

        Route::post('/mappings/import', [MappingImportController::class, 'import'])->name('api.mappings.import');
        Route::post('/mappings/preview', [MappingImportController::class, 'preview'])->name('api.mappings.preview');

    });

});
