<?php

use Illuminate\Support\Facades\Route;
use App\Modules\RiskManagement\Controllers\ControlMappingController;

Route::middleware('api')->prefix('api')->group(function () {

Route::middleware('auth:sanctum')->prefix('rmm')->name('rmm.')->group(function () {

    Route::prefix('control-mapping')->name('control-mapping.')->group(function () {

        // Suggest matches between risk descriptions and framework/local controls
        Route::post('/suggest',         [ControlMappingController::class, 'suggest'])->name('suggest');

        // Confirm / Reject a suggestion
        Route::post('/confirm',         [ControlMappingController::class, 'confirm'])->name('confirm');
        Route::post('/reject',          [ControlMappingController::class, 'reject'])->name('reject');

        // Manually map a risk to a control
        Route::post('/manual',          [ControlMappingController::class, 'manualMap'])->name('manual');

        // Remove a mapping
        Route::delete('/{riskRegisterId}/{frameworkControlId}', [ControlMappingController::class, 'destroy'])->name('destroy');

        // List mappings for a risk
        Route::get('/by-risk/{riskRegisterId}', [ControlMappingController::class, 'byRisk'])->name('by-risk');

        // Catalog browsing
        Route::get('/frameworks',       [ControlMappingController::class, 'frameworks'])->name('frameworks');
        Route::get('/local-controls',   [ControlMappingController::class, 'localControls'])->name('local-controls');
    });

    // Risk lifecycle
    Route::post('/risk/{risk}/lifecycle-transition', [\App\Modules\RiskManagement\Controllers\RiskRegisterController::class, 'transitionLifecycleApi'])->name('risk.lifecycle-transition');

    // Risk exposure
    Route::get('/risk/{risk}/exposure', [\App\Modules\RiskManagement\Controllers\RiskExposureController::class, 'show'])->name('risk.exposure');

    // Inherent (before-controls) scoring history + before-vs-after-controls feed
    Route::get('/risk/{risk}/inherent-history', [\App\Modules\RiskManagement\Controllers\RiskInherentScoreController::class, 'history'])->name('risk.inherent-history');
    Route::get('/inherent/before-after', [\App\Modules\RiskManagement\Controllers\RiskInherentScoreController::class, 'beforeAfter'])->name('risk.inherent.before-after');

    // Residual (after-controls) scoring history, trend series + manual override
    Route::get('/risk/{risk}/residual-history', [\App\Modules\RiskManagement\Controllers\ResidualRiskController::class, 'history'])->name('risk.residual-history');
    Route::get('/risk/{risk}/residual-trend', [\App\Modules\RiskManagement\Controllers\ResidualRiskController::class, 'trend'])->name('risk.residual-trend');
    Route::post('/risk/{risk}/residual-override', [\App\Modules\RiskManagement\Controllers\ResidualRiskController::class, 'override'])->name('risk.residual-override');

    // Vendors
    Route::get('/vendors', [\App\Modules\RiskManagement\Controllers\VendorController::class, 'index'])->name('vendors.index');
    Route::post('/vendors', [\App\Modules\RiskManagement\Controllers\VendorController::class, 'store'])->name('vendors.store');
    Route::get('/vendors/{vendor}', [\App\Modules\RiskManagement\Controllers\VendorController::class, 'show'])->name('vendors.show');
    Route::put('/vendors/{vendor}', [\App\Modules\RiskManagement\Controllers\VendorController::class, 'update'])->name('vendors.update');
    Route::delete('/vendors/{vendor}', [\App\Modules\RiskManagement\Controllers\VendorController::class, 'destroy'])->name('vendors.destroy');

    Route::get('/vendors/{vendor}/assessments', [\App\Modules\RiskManagement\Controllers\VendorAssessmentController::class, 'index'])->name('vendors.assessments.index');
    Route::post('/vendors/{vendor}/assessments', [\App\Modules\RiskManagement\Controllers\VendorAssessmentController::class, 'store'])->name('vendors.assessments.store');
    Route::get('/vendors/{vendor}/assessments/{assessment}', [\App\Modules\RiskManagement\Controllers\VendorAssessmentController::class, 'show'])->name('vendors.assessments.show');
    Route::put('/vendors/{vendor}/assessments/{assessment}', [\App\Modules\RiskManagement\Controllers\VendorAssessmentController::class, 'update'])->name('vendors.assessments.update');
    Route::post('/vendors/{vendor}/assessments/{assessment}/complete', [\App\Modules\RiskManagement\Controllers\VendorAssessmentController::class, 'complete'])->name('vendors.assessments.complete');
    Route::post('/vendors/{vendor}/assessments/{assessment}/responses', [\App\Modules\RiskManagement\Controllers\VendorAssessmentController::class, 'submitResponse'])->name('vendors.assessments.responses');

    // Snapshots
    Route::get('/snapshots', [\App\Modules\RiskManagement\Controllers\RiskSnapshotController::class, 'index'])->name('risk.snapshots.index');
    Route::post('/snapshots', [\App\Modules\RiskManagement\Controllers\RiskSnapshotController::class, 'store'])->name('risk.snapshots.store');
});

});
