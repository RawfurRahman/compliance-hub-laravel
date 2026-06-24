<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add lifecycle_status and exposure_value to risk_registers
        Schema::table('risk_registers', function (Blueprint $table) {
            $table->string('lifecycle_status', 30)->default('draft')->after('implementation_status');
            $table->decimal('exposure_value', 20, 2)->nullable()->after('computed_residual_rating');
            $table->index('lifecycle_status');
        });

        // 2. Extend risk_acceptances
        Schema::table('risk_acceptances', function (Blueprint $table) {
            $table->unsignedTinyInteger('residual_risk_score')->nullable()->after('status');
            $table->string('acceptance_criteria', 50)->nullable()->after('residual_risk_score');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null')->after('approved_by');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });

        // 3. Create risk_scenarios
        Schema::create('risk_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_register_id')->nullable()->constrained('risk_registers')->onDelete('cascade');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('threat_source', 100)->nullable();
            $table->string('threat_event', 255)->nullable();
            $table->text('vulnerability_factor')->nullable();
            $table->date('scenario_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Create risk_treatment_plans
        Schema::create('risk_treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_register_id')->constrained('risk_registers')->onDelete('cascade');
            $table->string('title', 255);
            $table->enum('treatment_type', ['avoid', 'reduce', 'transfer', 'accept'])->default('reduce');
            $table->text('description')->nullable();
            $table->text('controls_required')->nullable();
            $table->string('responsible_party', 255)->nullable();
            $table->decimal('budget_estimated', 15, 2)->nullable();
            $table->decimal('budget_actual', 15, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('target_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->string('status', 30)->default('planned');
            $table->unsignedTinyInteger('progress_pct')->default(0);
            $table->unsignedTinyInteger('effectiveness_rating')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Create risk_exposures
        Schema::create('risk_exposures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_register_id')->constrained('risk_registers')->onDelete('cascade');
            $table->string('exposure_type', 50)->default('financial');
            $table->decimal('inherent_exposure', 20, 2)->nullable();
            $table->decimal('residual_exposure', 20, 2)->nullable();
            $table->decimal('financial_amount', 20, 2)->nullable();
            $table->decimal('probability_pct', 5, 2)->nullable();
            $table->unsignedTinyInteger('impact_rating')->nullable();
            $table->string('currency', 3)->default('BDT');
            $table->timestamp('calculated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 6. Create risk_snapshots
        Schema::create('risk_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('snapshot_type', 50)->default('full');
            $table->json('snapshot_data')->nullable();
            $table->integer('total_risks')->default(0);
            $table->integer('critical_count')->default(0);
            $table->integer('high_count')->default(0);
            $table->integer('medium_count')->default(0);
            $table->integer('low_count')->default(0);
            $table->decimal('total_exposure', 20, 2)->nullable();
            $table->decimal('avg_inherent_score', 10, 2)->nullable();
            $table->decimal('avg_residual_score', 10, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('snapped_at')->useCurrent();
            $table->timestamps();
        });

        // 7. Create third_party_vendors
        Schema::create('third_party_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->string('vendor_name', 255);
            $table->string('vendor_code', 100)->nullable()->unique();
            $table->string('contact_name', 255)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('service_category', 100)->nullable();
            $table->string('criticality', 30)->default('medium');
            $table->string('risk_tier', 30)->nullable();
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->string('data_classification', 50)->nullable();
            $table->text('data_shared')->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        // 8. Create vendor_assessments
        Schema::create('vendor_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('third_party_vendors')->onDelete('cascade');
            $table->foreignId('assessor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('assessment_type', 50)->default('questionnaire');
            $table->date('assessment_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->string('status', 30)->default('pending');
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->string('risk_rating', 30)->nullable();
            $table->text('findings_summary')->nullable();
            $table->boolean('remediation_required')->default(false);
            $table->date('remediation_deadline')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 9. Create vendor_questionnaire_responses
        Schema::create('vendor_questionnaire_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_assessment_id')->constrained('vendor_assessments')->onDelete('cascade');
            $table->string('section', 255)->nullable();
            $table->string('question_key', 100);
            $table->text('question_text');
            $table->text('response_text')->nullable();
            $table->string('response_type', 30)->default('text');
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('max_score', 5, 2)->nullable();
            $table->string('evidence_file', 255)->nullable();
            $table->boolean('is_compliant')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
        });

        // 10. Create polymorphic risk_notes
        Schema::create('risk_notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('notable');
            $table->foreignId('user_id')->constrained('users');
            $table->string('type', 50)->default('comment');
            $table->text('content')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_notes');
        Schema::dropIfExists('vendor_questionnaire_responses');
        Schema::dropIfExists('vendor_assessments');
        Schema::dropIfExists('third_party_vendors');
        Schema::dropIfExists('risk_snapshots');
        Schema::dropIfExists('risk_exposures');
        Schema::dropIfExists('risk_treatment_plans');
        Schema::dropIfExists('risk_scenarios');

        Schema::table('risk_acceptances', function (Blueprint $table) {
            $table->dropColumn(['residual_risk_score', 'acceptance_criteria', 'reviewed_by', 'reviewed_at']);
        });

        Schema::table('risk_registers', function (Blueprint $table) {
            $table->dropIndex(['lifecycle_status']);
            $table->dropColumn(['lifecycle_status', 'exposure_value']);
        });
    }
};
