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

Route::middleware(['web', 'auth'])->group(function () {

    // Project-scoped governance routes
    Route::prefix('projects/{project}/governance')->name('governance.')->group(function () {

        // Policy CRUD
        Route::get('/', [PolicyController::class, 'index'])->name('policies.index');
        Route::get('/create', [PolicyController::class, 'create'])->name('policies.create');
        Route::post('/', [PolicyController::class, 'store'])->name('policies.store');
        Route::get('/{policy}', [PolicyController::class, 'show'])->name('policies.show');
        Route::get('/{policy}/edit', [PolicyController::class, 'edit'])->name('policies.edit');
        Route::put('/{policy}', [PolicyController::class, 'update'])->name('policies.update');
        Route::delete('/{policy}', [PolicyController::class, 'destroy'])->name('policies.destroy');

        // Policy lifecycle transitions
        Route::post('/{policy}/submit-review', [PolicyController::class, 'submitForReview'])->name('policies.submit-review');
        Route::post('/{policy}/publish', [PolicyController::class, 'publish'])->name('policies.publish');
        Route::post('/{policy}/deprecate', [PolicyController::class, 'deprecate'])->name('policies.deprecate');
        Route::post('/{policy}/archive', [PolicyController::class, 'archive'])->name('policies.archive');
        Route::post('/{policy}/reactivate', [PolicyController::class, 'reactivate'])->name('policies.reactivate');
        Route::post('/{policy}/expire', [PolicyController::class, 'expire'])->name('policies.expire');

        // Policy versions
        Route::get('/{policy}/versions', [PolicyController::class, 'versions'])->name('policies.versions');

        // Reviews
        Route::get('/{policy}/reviews', [PolicyReviewController::class, 'index'])->name('reviews.index');
        Route::post('/{policy}/reviews', [PolicyReviewController::class, 'store'])->name('reviews.store');
        Route::put('/{policy}/reviews/{review}', [PolicyReviewController::class, 'update'])->name('reviews.update');

        // Approvals
        Route::get('/{policy}/approvals', [PolicyApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/{policy}/approvals', [PolicyApprovalController::class, 'store'])->name('approvals.store');
        Route::put('/{policy}/approvals/{approval}/approve', [PolicyApprovalController::class, 'approve'])->name('approvals.approve');
        Route::put('/{policy}/approvals/{approval}/reject', [PolicyApprovalController::class, 'reject'])->name('approvals.reject');

        // Exceptions
        Route::get('/{policy}/exceptions', [PolicyExceptionController::class, 'index'])->name('exceptions.index');
        Route::post('/{policy}/exceptions', [PolicyExceptionController::class, 'store'])->name('exceptions.store');
        Route::put('/{policy}/exceptions/{exception}/approve', [PolicyExceptionController::class, 'approve'])->name('exceptions.approve');
        Route::put('/{policy}/exceptions/{exception}/reject', [PolicyExceptionController::class, 'reject'])->name('exceptions.reject');
        Route::put('/{policy}/exceptions/{exception}/revoke', [PolicyExceptionController::class, 'revoke'])->name('exceptions.revoke');

        // Waivers
        Route::get('/{policy}/waivers', [PolicyWaiverController::class, 'index'])->name('waivers.index');
        Route::post('/{policy}/waivers', [PolicyWaiverController::class, 'store'])->name('waivers.store');
        Route::put('/{policy}/waivers/{waiver}/approve', [PolicyWaiverController::class, 'approve'])->name('waivers.approve');
        Route::put('/{policy}/waivers/{waiver}/reject', [PolicyWaiverController::class, 'reject'])->name('waivers.reject');
        Route::put('/{policy}/waivers/{waiver}/revoke', [PolicyWaiverController::class, 'revoke'])->name('waivers.revoke');

        // Ownership
        Route::get('/{policy}/ownership', [OwnershipController::class, 'index'])->name('ownership.index');
        Route::post('/{policy}/ownership', [OwnershipController::class, 'store'])->name('ownership.store');
        Route::delete('/{policy}/ownership/{id}', [OwnershipController::class, 'destroy'])->name('ownership.destroy');

        // Stakeholders
        Route::get('/{policy}/stakeholders', [StakeholderController::class, 'index'])->name('stakeholders.index');
        Route::post('/{policy}/stakeholders', [StakeholderController::class, 'store'])->name('stakeholders.store');
        Route::delete('/{policy}/stakeholders/{stakeholder}', [StakeholderController::class, 'destroy'])->name('stakeholders.destroy');

        // Dashboard
        Route::get('/dashboard', [GovernanceDashboardController::class, 'index'])->name('dashboard');
        Route::post('/dashboard/snapshot', [GovernanceDashboardController::class, 'snapshot'])->name('dashboard.snapshot');
    });

    // Admin: Domains (global, not project-scoped)
    Route::prefix('admin/governance/domains')->name('admin.governance.domains.')->group(function () {
        Route::get('/', [DomainController::class, 'index'])->name('index');
        Route::post('/', [DomainController::class, 'store'])->name('store');
        Route::get('/{domain}', [DomainController::class, 'show'])->name('show');
        Route::put('/{domain}', [DomainController::class, 'update'])->name('update');
        Route::delete('/{domain}', [DomainController::class, 'destroy'])->name('destroy');
    });

    // Admin: SLA Rules (global)
    Route::prefix('admin/governance/sla-rules')->name('admin.governance.sla-rules.')->group(function () {
        Route::get('/', [SLARuleController::class, 'index'])->name('index');
        Route::post('/', [SLARuleController::class, 'store'])->name('store');
        Route::put('/{rule}', [SLARuleController::class, 'update'])->name('update');
        Route::delete('/{rule}', [SLARuleController::class, 'destroy'])->name('destroy');
    });
});
