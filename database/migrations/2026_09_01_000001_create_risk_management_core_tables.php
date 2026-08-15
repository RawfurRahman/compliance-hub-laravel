<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('risk_acceptance_requests')) {
            Schema::create('risk_acceptance_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('justification')->nullable();
                $table->text('conditions')->nullable();
                $table->string('status')->nullable();
                $table->date('expiry_date')->nullable();
                $table->text('approver_notes')->nullable();
                $table->dateTime('decided_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('risk_acceptances')) {
            Schema::create('risk_acceptances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('justification')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('status')->nullable();
                $table->integer('residual_risk_score')->nullable();
                $table->text('acceptance_criteria')->nullable();
                $table->dateTime('reviewed_at')->nullable();
                $table->timestamps();

                $table->index('risk_register_id');
                $table->index('status');
                $table->index('expiry_date');
            });
        }

        if (! Schema::hasTable('risk_appetite')) {
            Schema::create('risk_appetite', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('critical_threshold')->nullable();
                $table->integer('high_threshold')->nullable();
                $table->integer('medium_threshold')->nullable();
                $table->decimal('max_financial_exposure', 12, 2)->nullable();
                $table->decimal('target_residual_score', 12, 2)->nullable();
                $table->text('appetite_statement')->nullable();
                $table->date('effective_date')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('risk_attachments')) {
            Schema::create('risk_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('evidence_id')->nullable()->constrained('evidence')->nullOnDelete();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('filename')->nullable();
                $table->string('disk')->nullable();
                $table->string('path')->nullable();
                $table->string('mime_type')->nullable();
                $table->integer('file_size')->nullable();
                $table->string('attachment_type')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('risk_categories')) {
            Schema::create('risk_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('slug')->nullable();
                $table->string('color')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->nullable();
                $table->integer('sort_order')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('risk_comments')) {
            Schema::create('risk_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('body');
                $table->softDeletes();
                $table->timestamps();

                $table->index('risk_register_id');
            });
        }

        if (! Schema::hasTable('risk_departments')) {
            Schema::create('risk_departments', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('code')->nullable();
                $table->string('head_name')->nullable();
                $table->string('head_email')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('risk_exception_requests')) {
            Schema::create('risk_exception_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reason')->nullable();
                $table->text('compensating_controls')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('status')->nullable();
                $table->text('approver_notes')->nullable();
                $table->dateTime('decided_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('risk_exposures')) {
            Schema::create('risk_exposures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('exposure_type')->nullable();
                $table->decimal('inherent_exposure', 12, 2)->nullable();
                $table->decimal('residual_exposure', 12, 2)->nullable();
                $table->decimal('financial_amount', 12, 2)->nullable();
                $table->decimal('probability_pct', 12, 2)->nullable();
                $table->integer('impact_rating')->nullable();
                $table->string('currency')->nullable();
                $table->dateTime('calculated_at')->nullable();
                $table->timestamps();

                $table->index('risk_register_id');
                $table->index('calculated_at');
            });
        }

        if (! Schema::hasTable('risk_notes')) {
            Schema::create('risk_notes', function (Blueprint $table) {
                $table->id();
                $table->morphs('notable');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type')->nullable();
                $table->text('content')->nullable();
                $table->string('file_path')->nullable();
                $table->string('file_name')->nullable();
                $table->string('mime_type')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('risk_notifications')) {
            Schema::create('risk_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type')->nullable();
                $table->string('title')->nullable();
                $table->text('body')->nullable();
                $table->dateTime('read_at')->nullable();
                $table->boolean('emailed')->nullable();
                $table->timestamps();

                $table->index('risk_register_id');
                $table->index('read_at');
            });
        }

        if (! Schema::hasTable('risk_register_entries')) {
            Schema::create('risk_register_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('framework_control_id')->nullable()->constrained('framework_controls')->nullOnDelete();
                $table->string('risk_id')->nullable();
                $table->string('risk_owner')->nullable();
                $table->string('department')->nullable();
                $table->date('date_identified')->nullable();
                $table->string('risk_category')->nullable();
                $table->text('risk_description')->nullable();
                $table->integer('inherent_likelihood')->nullable();
                $table->integer('inherent_impact')->nullable();
                $table->integer('inherent_score')->nullable();
                $table->string('inherent_risk_level')->nullable();
                $table->text('recommended_control')->nullable();
                $table->string('treatment_decision')->nullable();
                $table->text('treatment_description')->nullable();
                $table->string('treatment_status')->nullable();
                $table->date('treatment_start_date')->nullable();
                $table->date('treatment_end_date')->nullable();
                $table->integer('treatment_progress')->nullable();
                $table->integer('residual_likelihood')->nullable();
                $table->integer('residual_impact')->nullable();
                $table->integer('residual_score')->nullable();
                $table->string('residual_risk_level')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('project_id');
                $table->index('inherent_risk_level');
            });
        }

        if (! Schema::hasTable('risk_review_cycles')) {
            Schema::create('risk_review_cycles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name')->nullable();
                $table->string('frequency')->nullable();
                $table->date('next_review_date')->nullable();
                $table->date('last_review_date')->nullable();
                $table->boolean('is_active')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('risk_reviews')) {
            Schema::create('risk_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('review_date')->nullable();
                $table->string('outcome')->nullable();
                $table->text('findings')->nullable();
                $table->text('recommendations')->nullable();
                $table->date('next_review_date')->nullable();
                $table->timestamps();

                $table->index('risk_register_id');
            });
        }

        if (! Schema::hasTable('risk_scenarios')) {
            Schema::create('risk_scenarios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->nullable()->constrained('risk_registers')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->string('threat_source', 100)->nullable();
                $table->string('threat_event')->nullable();
                $table->string('vulnerability_factor')->nullable();
                $table->date('scenario_date')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index('risk_register_id');
                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('risk_status_history')) {
            Schema::create('risk_status_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('from_status')->nullable();
                $table->string('to_status')->nullable();
                $table->text('reason')->nullable();
                $table->dateTime('changed_at')->nullable();
            });
        }

        if (! Schema::hasTable('risk_treatment_plans')) {
            Schema::create('risk_treatment_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('assessment_finding_id')->nullable()->constrained('assessment_findings')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title')->nullable();
                $table->string('treatment_type')->nullable();
                $table->text('description')->nullable();
                $table->text('controls_required')->nullable();
                $table->string('responsible_party')->nullable();
                $table->decimal('budget_estimated', 12, 2)->nullable();
                $table->decimal('budget_actual', 12, 2)->nullable();
                $table->date('start_date')->nullable();
                $table->date('target_date')->nullable();
                $table->date('completion_date')->nullable();
                $table->string('status')->nullable();
                $table->integer('progress_pct')->nullable();
                $table->integer('effectiveness_rating')->nullable();
                $table->text('notes')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index('risk_register_id');
                $table->index('assessment_finding_id');
                $table->index('status');
                $table->index('target_date');
            });
        }

        if (! Schema::hasTable('risk_treatments')) {
            Schema::create('risk_treatments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('treatment_type')->nullable();
                $table->text('description')->nullable();
                $table->string('status')->nullable();
                $table->integer('progress')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('responsible_person')->nullable();
                $table->string('responsible_department')->nullable();
                $table->decimal('estimated_cost', 12, 2)->nullable();
                $table->decimal('actual_cost', 12, 2)->nullable();
                $table->text('notes')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('third_party_vendors')) {
            Schema::create('third_party_vendors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('vendor_name');
                $table->string('vendor_code', 100)->nullable()->unique();
                $table->string('contact_name')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone', 50)->nullable();
                $table->string('website')->nullable();
                $table->string('service_category', 100)->nullable();
                $table->string('criticality')->nullable();
                $table->string('risk_tier', 30)->nullable();
                $table->date('contract_start')->nullable();
                $table->date('contract_end')->nullable();
                $table->string('data_classification', 50)->nullable();
                $table->text('data_shared')->nullable();
                $table->string('status')->nullable();
                $table->text('notes')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index('project_id');
                $table->index('vendor_name');
                $table->index('service_category');
                $table->index('criticality');
                $table->index('status');
                $table->index('risk_tier');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('third_party_vendors');
        Schema::dropIfExists('risk_treatments');
        Schema::dropIfExists('risk_treatment_plans');
        Schema::dropIfExists('risk_status_history');
        Schema::dropIfExists('risk_scenarios');
        Schema::dropIfExists('risk_reviews');
        Schema::dropIfExists('risk_review_cycles');
        Schema::dropIfExists('risk_register_entries');
        Schema::dropIfExists('risk_notifications');
        Schema::dropIfExists('risk_notes');
        Schema::dropIfExists('risk_exposures');
        Schema::dropIfExists('risk_exception_requests');
        Schema::dropIfExists('risk_departments');
        Schema::dropIfExists('risk_comments');
        Schema::dropIfExists('risk_categories');
        Schema::dropIfExists('risk_attachments');
        Schema::dropIfExists('risk_appetite');
        Schema::dropIfExists('risk_acceptances');
        Schema::dropIfExists('risk_acceptance_requests');
    }
};