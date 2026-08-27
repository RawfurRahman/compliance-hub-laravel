<?php

use App\Modules\RiskManagement\Controllers\ControlCatalogController;
use App\Modules\RiskManagement\Controllers\ControlMappingDashboardController;
use App\Modules\RiskManagement\Controllers\RiskExposureController;
use App\Modules\RiskManagement\Controllers\RiskImportController;
use App\Modules\RiskManagement\Controllers\RiskRegisterController;
use App\Modules\RiskManagement\Controllers\RiskScenarioController;
use App\Modules\RiskManagement\Controllers\RiskSnapshotController;
use App\Modules\RiskManagement\Controllers\RiskTreatmentPlanController;
use App\Modules\RiskManagement\Controllers\VendorAssessmentController;
use App\Modules\RiskManagement\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {

    // Risk Management Module Routes
    Route::prefix('projects/{project}/risk-register')->name('risk-register.')->group(function () {
        Route::get('/', [RiskRegisterController::class, 'index'])->name('index');
        Route::get('/create', [RiskRegisterController::class, 'create'])->name('create');
        Route::post('/', [RiskRegisterController::class, 'store'])->name('store');
        Route::get('/{risk}/edit', [RiskRegisterController::class, 'edit'])->name('edit');
        Route::put('/{risk}', [RiskRegisterController::class, 'update'])->name('update');
        Route::delete('/{risk}', [RiskRegisterController::class, 'destroy'])->name('destroy');
        Route::get('/heatmap', [RiskRegisterController::class, 'heatmap'])->name('heatmap');
        Route::get('/export-pdf', [RiskRegisterController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/export-csv', [RiskRegisterController::class, 'exportExcel'])->name('export-csv');
        Route::post('/{risk}/transition', [RiskRegisterController::class, 'transition'])->name('transition');
        Route::post('/{risk}/evidence', [RiskRegisterController::class, 'attachEvidence'])->name('evidence');
        Route::post('/{risk}/comment', [RiskRegisterController::class, 'addComment'])->name('comment');

        // Detailed risk sub-actions
        Route::post('/{risk}/map-control', [RiskRegisterController::class, 'mapControl'])->name('map-control');
        Route::delete('/{risk}/unmap-control/{control}', [RiskRegisterController::class, 'unmapControl'])->name('unmap-control');
        Route::post('/{risk}/treatments', [RiskRegisterController::class, 'addTreatment'])->name('add-treatment');
        Route::delete('/{risk}/treatments/{id}', [RiskRegisterController::class, 'deleteTreatment'])->name('delete-treatment');
        Route::post('/{risk}/reviews', [RiskRegisterController::class, 'submitReview'])->name('submit-review');
        Route::post('/{risk}/kris', [RiskRegisterController::class, 'addKri'])->name('add-kri');
        Route::delete('/{risk}/kris/{id}', [RiskRegisterController::class, 'deleteKri'])->name('delete-kri');

        // Stage 4 — Control Mapping Engine
        Route::post('/{risk}/suggest-mappings', [RiskRegisterController::class, 'suggestMappings'])->name('suggest-mappings');
        Route::post('/{risk}/accept-suggestion/{mapping}', [RiskRegisterController::class, 'acceptSuggestion'])->name('accept-suggestion');
        Route::post('/{risk}/reject-suggestion/{mapping}', [RiskRegisterController::class, 'rejectSuggestion'])->name('reject-suggestion');

        // Lifecycle
        Route::post('/{risk}/lifecycle-transition', [RiskRegisterController::class, 'transitionLifecycle'])->name('lifecycle-transition');

        // Risk Scenarios
        Route::get('/{risk}/scenarios', [RiskScenarioController::class, 'index'])->name('scenarios.index');
        Route::post('/{risk}/scenarios', [RiskScenarioController::class, 'store'])->name('scenarios.store');
        Route::put('/{risk}/scenarios/{scenario}', [RiskScenarioController::class, 'update'])->name('scenarios.update');
        Route::delete('/{risk}/scenarios/{scenario}', [RiskScenarioController::class, 'destroy'])->name('scenarios.destroy');

        // Treatment Plans
        Route::get('/{risk}/treatment-plans', [RiskTreatmentPlanController::class, 'index'])->name('treatment-plans.index');
        Route::post('/{risk}/treatment-plans', [RiskTreatmentPlanController::class, 'store'])->name('treatment-plans.store');
        Route::put('/{risk}/treatment-plans/{plan}', [RiskTreatmentPlanController::class, 'update'])->name('treatment-plans.update');
        Route::delete('/{risk}/treatment-plans/{plan}', [RiskTreatmentPlanController::class, 'destroy'])->name('treatment-plans.destroy');

        // Exposure
        Route::get('/{risk}/exposure', [RiskExposureController::class, 'show'])->name('exposure.show');
        Route::post('/{risk}/exposure/calculate', [RiskExposureController::class, 'calculate'])->name('exposure.calculate');

        // Snapshots
        Route::get('/snapshots', [RiskSnapshotController::class, 'index'])->name('snapshots.index');
        Route::post('/snapshots', [RiskSnapshotController::class, 'store'])->name('snapshots.store');

        // Workbook Importer Routes
        Route::get('/import', [RiskImportController::class, 'showImportForm'])->name('import.show');
        Route::post('/import/dry-run', [RiskImportController::class, 'dryRun'])->name('import.dry-run');
        Route::post('/import/confirm', [RiskImportController::class, 'confirmImport'])->name('import.confirm');
    });

    // Vendor Management
    Route::prefix('projects/{project}/vendors')->name('vendors.')->group(function () {
        Route::get('/', [VendorController::class, 'index'])->name('index');
        Route::post('/', [VendorController::class, 'store'])->name('store');
        Route::get('/{vendor}', [VendorController::class, 'show'])->name('show');
        Route::put('/{vendor}', [VendorController::class, 'update'])->name('update');
        Route::delete('/{vendor}', [VendorController::class, 'destroy'])->name('destroy');

        Route::get('/{vendor}/assessments', [VendorAssessmentController::class, 'index'])->name('assessments.index');
        Route::post('/{vendor}/assessments', [VendorAssessmentController::class, 'store'])->name('assessments.store');
        Route::get('/{vendor}/assessments/{assessment}', [VendorAssessmentController::class, 'show'])->name('assessments.show');
        Route::put('/{vendor}/assessments/{assessment}', [VendorAssessmentController::class, 'update'])->name('assessments.update');
        Route::delete('/{vendor}/assessments/{assessment}', [VendorAssessmentController::class, 'destroy'])->name('assessments.destroy');
        Route::post('/{vendor}/assessments/{assessment}/complete', [VendorAssessmentController::class, 'complete'])->name('assessments.complete');
        Route::post('/{vendor}/assessments/{assessment}/responses', [VendorAssessmentController::class, 'submitResponse'])->name('assessments.responses');
        Route::get('/{vendor}/assessments/{assessment}/detail', [VendorAssessmentController::class, 'detail'])->name('assessments.detail');
        Route::get('/{vendor}/assessments/{assessment}/summary', [VendorAssessmentController::class, 'summary'])->name('assessments.summary');
        Route::post('/{vendor}/assessments/{assessment}/run-ai', [VendorAssessmentController::class, 'runAiAnalysis'])->name('assessments.run-ai');
        Route::post('/{vendor}/assessments/{assessment}/responses/{response}/flag', [VendorAssessmentController::class, 'flagForReview'])->name('assessments.responses.flag');
    });

    // Internal Control Catalog (admin)
    Route::prefix('admin/controls')->name('admin.controls.')->group(function () {
        Route::get('/', [ControlCatalogController::class, 'index'])->name('index');
        Route::post('/', [ControlCatalogController::class, 'store'])->name('store');
        Route::get('/{control}/edit', [ControlCatalogController::class, 'edit'])->name('edit');
        Route::put('/{control}', [ControlCatalogController::class, 'update'])->name('update');
        Route::delete('/{control}', [ControlCatalogController::class, 'destroy'])->name('destroy');
        Route::post('/import', [ControlCatalogController::class, 'import'])->name('import');
    });

    // Control Mapping Dashboard (admin)
    Route::get('admin/control-mappings', [ControlMappingDashboardController::class, 'index'])->name('admin.control-mappings.index');
    Route::post('admin/control-mappings/{mapping}/confirm', [ControlMappingDashboardController::class, 'confirm'])->name('admin.control-mappings.confirm');
    Route::post('admin/control-mappings/{mapping}/reject', [ControlMappingDashboardController::class, 'reject'])->name('admin.control-mappings.reject');
    Route::get('admin/control-mappings/export', [ControlMappingDashboardController::class, 'export'])->name('admin.control-mappings.export');

});
