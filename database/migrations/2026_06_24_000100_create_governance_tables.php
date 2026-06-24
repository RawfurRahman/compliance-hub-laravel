<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->nullable()->constrained('domains')->onDelete('set null');
            $table->string('title');
            $table->string('slug', 100)->unique();
            $table->string('policy_number', 50)->unique();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft');
            $table->date('effective_date')->nullable();
            $table->date('expires_at')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('department', 100)->nullable();
            $table->string('business_unit', 100)->nullable();
            $table->unsignedInteger('current_version')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('domain_id');
            $table->index('owner_user_id');
            $table->index('published_at');
            $table->index('expires_at');
        });

        Schema::create('policy_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->onDelete('cascade');
            $table->unsignedInteger('version_number');
            $table->string('title');
            $table->longText('content');
            $table->text('change_summary')->nullable();
            $table->string('status', 20);
            $table->date('effective_date')->nullable();
            $table->date('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['policy_id', 'version_number']);
            $table->index('policy_id');
        });

        Schema::create('policy_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->onDelete('cascade');
            $table->foreignId('policy_version_id')->nullable()->constrained('policy_versions')->onDelete('set null');
            $table->foreignId('reviewer_user_id')->constrained('users')->onDelete('cascade');
            $table->string('review_type', 20)->default('scheduled');
            $table->text('comments')->nullable();
            $table->string('status', 20)->default('pending');
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('policy_id');
            $table->index('reviewer_user_id');
            $table->index('status');
        });

        Schema::create('policy_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->onDelete('cascade');
            $table->foreignId('policy_version_id')->nullable()->constrained('policy_versions')->onDelete('set null');
            $table->foreignId('approver_user_id')->constrained('users')->onDelete('cascade');
            $table->string('approval_type', 20)->default('initial');
            $table->string('status', 20)->default('pending');
            $table->text('comments')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('policy_id');
            $table->index('approver_user_id');
            $table->index('status');
        });

        Schema::create('policy_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->onDelete('cascade');
            $table->foreignId('policy_version_id')->constrained('policy_versions')->onDelete('cascade');
            $table->foreignId('published_by')->constrained('users')->onDelete('cascade');
            $table->string('method', 20)->default('manual');
            $table->string('audience', 100)->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('published_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('policy_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->onDelete('cascade');
            $table->foreignId('policy_version_id')->nullable()->constrained('policy_versions')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->text('justification');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status', 20)->default('pending');
            $table->date('effective_date');
            $table->date('expires_at');
            $table->string('department', 100)->nullable();
            $table->text('risk_acceptance')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('policy_id');
            $table->index('status');
        });

        Schema::create('policy_waivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->onDelete('cascade');
            $table->foreignId('policy_version_id')->nullable()->constrained('policy_versions')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->text('justification');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status', 20)->default('pending');
            $table->date('effective_date');
            $table->date('expires_at');
            $table->string('department', 100)->nullable();
            $table->text('compensating_controls')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('policy_id');
            $table->index('status');
        });

        Schema::create('ownership_matrix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('role', 50);
            $table->string('department', 100)->nullable();
            $table->string('business_unit', 100)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->index('policy_id');
            $table->index('user_id');
        });

        Schema::create('stakeholders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('stakeholder_type', 50);
            $table->string('department', 100)->nullable();
            $table->string('business_unit', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('policy_id');
        });

        Schema::create('sla_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->nullable()->constrained('policies')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_event', 50);
            $table->string('action_type', 50);
            $table->unsignedInteger('sla_hours');
            $table->unsignedInteger('escalation_interval_hours')->nullable();
            $table->foreignId('escalation_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('governance_metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('cascade');
            $table->foreignId('domain_id')->nullable()->constrained('domains')->onDelete('set null');
            $table->string('snapshot_type', 50)->default('overall');
            $table->unsignedInteger('total_policies')->default(0);
            $table->unsignedInteger('published_policies')->default(0);
            $table->unsignedInteger('draft_policies')->default(0);
            $table->unsignedInteger('under_review_policies')->default(0);
            $table->unsignedInteger('expired_policies')->default(0);
            $table->unsignedInteger('overdue_reviews')->default(0);
            $table->unsignedInteger('pending_approvals')->default(0);
            $table->unsignedInteger('active_waivers')->default(0);
            $table->unsignedInteger('active_exceptions')->default(0);
            $table->unsignedInteger('sla_breaches')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('snapped_at')->useCurrent();
            $table->timestamps();

            $table->index('snapshot_type');
            $table->index('snapped_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_metric_snapshots');
        Schema::dropIfExists('sla_rules');
        Schema::dropIfExists('stakeholders');
        Schema::dropIfExists('ownership_matrix');
        Schema::dropIfExists('policy_waivers');
        Schema::dropIfExists('policy_exceptions');
        Schema::dropIfExists('policy_publications');
        Schema::dropIfExists('policy_approvals');
        Schema::dropIfExists('policy_reviews');
        Schema::dropIfExists('policy_versions');
        Schema::dropIfExists('policies');
        Schema::dropIfExists('domains');
    }
};
