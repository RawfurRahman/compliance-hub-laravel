<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated historical table for all INHERENT risk scoring outputs.
 *
 * Stores the formula version and a verbatim input snapshot on every row so a
 * score can be reconstructed later even after business rules change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_inherent_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_register_id')->nullable()
                ->constrained('risk_registers')->cascadeOnDelete();

            // Calculated values
            $table->unsignedSmallInteger('tv_score');
            $table->integer('inherent_score');
            $table->string('severity_band', 20);
            $table->string('appetite_status', 20);
            $table->unsignedTinyInteger('heatmap_likelihood');
            $table->unsignedTinyInteger('heatmap_impact');
            $table->decimal('risk_ranking', 6, 2)->default(0);

            // Reproducibility metadata
            $table->string('formula_version', 20);
            $table->json('input_snapshot');   // verbatim raw inputs
            $table->json('explanation');      // derivation / audit trail
            $table->string('source', 30)->default('manual'); // import|manual|snapshot
            $table->foreignId('recorded_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent(); // append-only, no updated_at

            $table->index(['risk_register_id', 'formula_version']);
            $table->index('severity_band');
            $table->index('appetite_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_inherent_scores');
    }
};
