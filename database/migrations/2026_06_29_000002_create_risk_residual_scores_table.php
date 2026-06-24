<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated historical table for all RESIDUAL (after-controls) scoring outputs.
 *
 * Separate from risk_inherent_scores so inherent and residual can be plotted as
 * independent series. Stores formula version + input snapshot for exact later
 * reconstruction and manual-override audit metadata.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_residual_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_register_id')->nullable()
                ->constrained('risk_registers')->cascadeOnDelete();

            // Calculated values
            $table->integer('inherent_score');
            $table->integer('residual_score');
            $table->string('severity_band', 20);
            $table->string('appetite_status', 20);
            $table->decimal('reduction_pct', 5, 2)->default(0);
            $table->unsignedTinyInteger('heatmap_likelihood');
            $table->unsignedTinyInteger('heatmap_impact');
            $table->string('trend_direction', 20)->default('stable');

            // Manual override audit
            $table->boolean('manual_override')->default(false);
            $table->string('override_reason', 500)->nullable();

            // Reproducibility metadata
            $table->string('formula_version', 20);
            $table->json('input_snapshot');
            $table->json('explanation');
            $table->string('source', 30)->default('manual'); // import|manual|snapshot|override|trigger
            $table->foreignId('recorded_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent(); // append-only, no updated_at

            $table->index(['risk_register_id', 'formula_version']);
            $table->index('severity_band');
            $table->index('appetite_status');
            $table->index('trend_direction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_residual_scores');
    }
};
