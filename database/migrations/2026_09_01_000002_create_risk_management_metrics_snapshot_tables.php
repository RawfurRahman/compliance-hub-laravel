<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('risk_heatmap_snapshots')) {
            Schema::create('risk_heatmap_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('snapshot_type');
                $table->json('matrix_data');
                $table->integer('total_risks');
                $table->integer('critical_count');
                $table->integer('high_count');
                $table->integer('medium_count');
                $table->integer('low_count');
                $table->dateTime('snapped_at');

                $table->index('project_id');
            });
        }

        if (! Schema::hasTable('risk_inherent_scores')) {
            Schema::create('risk_inherent_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->nullable()->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('tv_score');
                $table->integer('inherent_score');
                $table->string('severity_band');
                $table->string('appetite_status');
                $table->integer('heatmap_likelihood');
                $table->integer('heatmap_impact');
                $table->decimal('risk_ranking', 8, 2);
                $table->string('formula_version');
                $table->json('input_snapshot')->nullable();
                $table->json('explanation')->nullable();
                $table->string('source');
                $table->timestamp('created_at')->nullable();

                $table->index('risk_register_id');
                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('risk_kpi_metrics')) {
            Schema::create('risk_kpi_metrics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('kpi_name');
                $table->string('category')->nullable();
                $table->decimal('target_value', 12, 2)->nullable();
                $table->decimal('actual_value', 12, 2)->nullable();
                $table->decimal('variance', 12, 2)->nullable();
                $table->string('unit')->nullable();
                $table->string('rag_status')->nullable();
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('risk_kri_metrics')) {
            Schema::create('risk_kri_metrics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('kri_name');
                $table->string('unit')->nullable();
                $table->decimal('threshold_green', 12, 2)->nullable();
                $table->decimal('threshold_amber', 12, 2)->nullable();
                $table->decimal('threshold_red', 12, 2)->nullable();
                $table->decimal('current_value', 12, 2)->nullable();
                $table->string('rag_status')->nullable();
                $table->date('measured_at')->nullable();
                $table->timestamps();

                $table->index('risk_register_id');
            });
        }

        if (! Schema::hasTable('risk_residual_scores')) {
            Schema::create('risk_residual_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->nullable()->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('inherent_score');
                $table->integer('residual_score');
                $table->string('severity_band');
                $table->string('appetite_status');
                $table->decimal('reduction_pct', 8, 2);
                $table->integer('heatmap_likelihood');
                $table->integer('heatmap_impact');
                $table->string('trend_direction');
                $table->boolean('manual_override')->default(false);
                $table->text('override_reason')->nullable();
                $table->string('formula_version');
                $table->json('input_snapshot')->nullable();
                $table->json('explanation')->nullable();
                $table->string('source');
                $table->timestamp('created_at')->nullable();

                $table->index('risk_register_id');
                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('risk_scores_history')) {
            Schema::create('risk_scores_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('tv_score');
                $table->integer('lh_score');
                $table->integer('rating_score');
                $table->integer('threat_level_t');
                $table->integer('vulnerability_level_av');
                $table->decimal('control_effectiveness', 8, 2);
                $table->string('formula_version');
                $table->integer('residual_tv');
                $table->integer('residual_lh');
                $table->integer('residual_rating');
                $table->dateTime('recorded_at')->nullable();

                $table->index('risk_register_id');
            });
        }

        if (! Schema::hasTable('risk_snapshots')) {
            Schema::create('risk_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('snapshot_type');
                $table->json('snapshot_data');
                $table->integer('total_risks');
                $table->integer('critical_count');
                $table->integer('high_count');
                $table->integer('medium_count');
                $table->integer('low_count');
                $table->decimal('total_exposure', 12, 2)->nullable();
                $table->decimal('avg_inherent_score', 12, 2)->nullable();
                $table->decimal('avg_residual_score', 12, 2)->nullable();
                $table->json('metadata')->nullable();
                $table->dateTime('snapped_at');
                $table->timestamps();

                $table->index('project_id');
                $table->index('snapshot_type');
                $table->index('snapped_at');
            });
        }

        if (! Schema::hasTable('remediation_metrics')) {
            Schema::create('remediation_metrics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->string('bucket_range')->nullable();
                $table->integer('issue_count')->nullable();
                $table->string('scope');
                $table->integer('total_items');
                $table->integer('open_items');
                $table->integer('closed_items');
                $table->integer('overdue_count');
                $table->decimal('mttr_hours', 10, 2)->nullable();
                $table->decimal('mtta_hours', 10, 2)->nullable();
                $table->decimal('mt_assign_hours', 10, 2)->nullable();
                $table->decimal('mttc_hours', 10, 2)->nullable();
                $table->decimal('closure_rate', 8, 2)->nullable();
                $table->json('aging_buckets')->nullable();
                $table->json('breakdown')->nullable();
                $table->dateTime('calculated_at');
                $table->timestamps();

                $table->index('scope');
                $table->index('project_id');
                $table->index('calculated_at');
            });
        }

        if (! Schema::hasTable('maturity_score_snapshots')) {
            Schema::create('maturity_score_snapshots', function (Blueprint $table) {
                $table->id();
                $table->date('snapshot_date');
                $table->string('dimension');
                $table->decimal('score_value', 3, 1);
                $table->integer('sample_size');
                $table->text('calculation_notes')->nullable();
                $table->timestamps();

                $table->index('snapshot_date');
                $table->index('dimension');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maturity_score_snapshots');
        Schema::dropIfExists('remediation_metrics');
        Schema::dropIfExists('risk_snapshots');
        Schema::dropIfExists('risk_scores_history');
        Schema::dropIfExists('risk_residual_scores');
        Schema::dropIfExists('risk_kri_metrics');
        Schema::dropIfExists('risk_kpi_metrics');
        Schema::dropIfExists('risk_inherent_scores');
        Schema::dropIfExists('risk_heatmap_snapshots');
    }
};