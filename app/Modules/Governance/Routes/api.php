<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Governance\Controllers\PolicyController;
use App\Modules\Governance\Controllers\PolicyReviewController;
use App\Modules\Governance\Controllers\PolicyApprovalController;
use App\Modules\Governance\Controllers\PolicyExceptionController;
use App\Modules\Governance\Controllers\PolicyWaiverController;
use App\Modules\Governance\Controllers\DomainController;
use App\Modules\Governance\Controllers\OwnershipController;
use App\Modules\Governance\Controllers\StakeholderController;
use App\Modules\Governance\Controllers\SLARuleController;
use App\Modules\Governance\Controllers\GovernanceDashboardController;

Route::middleware('api')->prefix('api')->group(function () {

    Route::middleware(['web', 'auth'])->prefix('governance')->name('governance-api.')->group(function () {

        // Policies
        Route::get('policies', [PolicyController::class, 'index'])->name('policies.index');
        Route::post('policies', [PolicyController::class, 'store'])->name('policies.store');
        Route::get('policies/{policy}', [PolicyController::class, 'show'])->name('policies.show');
        Route::put('policies/{policy}', [PolicyController::class, 'update'])->name('policies.update');
        Route::delete('policies/{policy}', [PolicyController::class, 'destroy'])->name('policies.destroy');

        // Lifecycle
        Route::post('policies/{policy}/submit-review', [PolicyController::class, 'submitForReview'])->name('policies.submit-review');
        Route::post('policies/{policy}/publish', [PolicyController::class, 'publish'])->name('policies.publish');
        Route::post('policies/{policy}/deprecate', [PolicyController::class, 'deprecate'])->name('policies.deprecate');
        Route::post('policies/{policy}/archive', [PolicyController::class, 'archive'])->name('policies.archive');
        Route::post('policies/{policy}/reactivate', [PolicyController::class, 'reactivate'])->name('policies.reactivate');
        Route::post('policies/{policy}/expire', [PolicyController::class, 'expire'])->name('policies.expire');

        // Versions
        Route::get('policies/{policy}/versions', [PolicyController::class, 'versions'])->name('policies.versions');

        // Reviews
        Route::get('policies/{policy}/reviews', [PolicyReviewController::class, 'index'])->name('reviews.index');
        Route::post('policies/{policy}/reviews', [PolicyReviewController::class, 'store'])->name('reviews.store');
        Route::put('policies/{policy}/reviews/{review}', [PolicyReviewController::class, 'update'])->name('reviews.update');

        // Approvals
        Route::get('policies/{policy}/approvals', [PolicyApprovalController::class, 'index'])->name('approvals.index');
        Route::post('policies/{policy}/approvals', [PolicyApprovalController::class, 'store'])->name('approvals.store');
        Route::put('policies/{policy}/approvals/{approval}/approve', [PolicyApprovalController::class, 'approve'])->name('approvals.approve');
        Route::put('policies/{policy}/approvals/{approval}/reject', [PolicyApprovalController::class, 'reject'])->name('approvals.reject');

        // Exceptions
        Route::get('policies/{policy}/exceptions', [PolicyExceptionController::class, 'index'])->name('exceptions.index');
        Route::post('exceptions', [PolicyExceptionController::class, 'store'])->name('exceptions.store');
        Route::put('exceptions/{exception}/approve', [PolicyExceptionController::class, 'approve'])->name('exceptions.approve');
        Route::put('exceptions/{exception}/reject', [PolicyExceptionController::class, 'reject'])->name('exceptions.reject');
        Route::put('exceptions/{exception}/revoke', [PolicyExceptionController::class, 'revoke'])->name('exceptions.revoke');

        // Waivers
        Route::get('policies/{policy}/waivers', [PolicyWaiverController::class, 'index'])->name('waivers.index');
        Route::post('waivers', [PolicyWaiverController::class, 'store'])->name('waivers.store');
        Route::put('waivers/{waiver}/approve', [PolicyWaiverController::class, 'approve'])->name('waivers.approve');
        Route::put('waivers/{waiver}/reject', [PolicyWaiverController::class, 'reject'])->name('waivers.reject');
        Route::put('waivers/{waiver}/revoke', [PolicyWaiverController::class, 'revoke'])->name('waivers.revoke');

        // Ownership
        Route::get('policies/{policy}/ownership', [OwnershipController::class, 'index'])->name('ownership.index');
        Route::post('policies/{policy}/ownership', [OwnershipController::class, 'store'])->name('ownership.store');
        Route::delete('policies/{policy}/ownership/{id}', [OwnershipController::class, 'destroy'])->name('ownership.destroy');

        // Stakeholders
        Route::get('policies/{policy}/stakeholders', [StakeholderController::class, 'index'])->name('stakeholders.index');
        Route::post('policies/{policy}/stakeholders', [StakeholderController::class, 'store'])->name('stakeholders.store');
        Route::delete('policies/{policy}/stakeholders/{stakeholder}', [StakeholderController::class, 'destroy'])->name('stakeholders.destroy');

        // Domains
        Route::get('domains', [DomainController::class, 'index'])->name('domains.index');
        Route::post('domains', [DomainController::class, 'store'])->name('domains.store');
        Route::get('domains/{domain}', [DomainController::class, 'show'])->name('domains.show');
        Route::put('domains/{domain}', [DomainController::class, 'update'])->name('domains.update');
        Route::delete('domains/{domain}', [DomainController::class, 'destroy'])->name('domains.destroy');

        // SLA Rules
        Route::get('sla-rules', [SLARuleController::class, 'index'])->name('sla-rules.index');
        Route::post('sla-rules', [SLARuleController::class, 'store'])->name('sla-rules.store');
        Route::put('sla-rules/{rule}', [SLARuleController::class, 'update'])->name('sla-rules.update');
        Route::delete('sla-rules/{rule}', [SLARuleController::class, 'destroy'])->name('sla-rules.destroy');

        // Dashboard
        Route::get('dashboard', [GovernanceDashboardController::class, 'index'])->name('dashboard');
        Route::post('dashboard/snapshot', [GovernanceDashboardController::class, 'snapshot'])->name('dashboard.snapshot');
    });
});
