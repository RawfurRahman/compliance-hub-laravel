<?php

use Illuminate\Support\Facades\Route;
use App\Modules\TrustCenter\Controllers\PublicTrustCenterController;
use App\Modules\TrustCenter\Controllers\TrustCenterController;

Route::middleware('web')->group(function () {
    Route::get('/trust/{slug}', [PublicTrustCenterController::class, 'show'])
        ->name('trust-center.public.show');

    Route::post('/trust/{slug}/request-access', [PublicTrustCenterController::class, 'requestAccess'])
        ->name('trust-center.public.request-access');

    Route::post('/trust/{slug}/questionnaire', [PublicTrustCenterController::class, 'submitQuestionnaire'])
        ->name('trust-center.public.questionnaire');
});

Route::middleware(['web', 'auth'])->prefix('admin/trust-centers')->name('admin.trust-centers.')->group(function () {
    Route::get('/', [TrustCenterController::class, 'index'])->name('index');
    Route::get('/{trustCenter}/overview', [TrustCenterController::class, 'overview'])->name('overview');

    Route::get('/{trustCenter}/edit', [TrustCenterController::class, 'edit'])->name('edit');
    Route::put('/{trustCenter}', [TrustCenterController::class, 'update'])->name('update');

    Route::get('/{trustCenter}/requests', [TrustCenterController::class, 'requests'])->name('requests');
    Route::post('/{trustCenter}/requests/{accessRequest}/approve', [TrustCenterController::class, 'approve'])->name('requests.approve');
    Route::post('/{trustCenter}/requests/{accessRequest}/deny', [TrustCenterController::class, 'deny'])->name('requests.deny');

    Route::get('/{trustCenter}/questionnaires', [TrustCenterController::class, 'questionnaires'])->name('questionnaires');
    Route::post('/{trustCenter}/questionnaires/{questionnaire}/responded', [TrustCenterController::class, 'markResponded'])->name('questionnaires.responded');
});
