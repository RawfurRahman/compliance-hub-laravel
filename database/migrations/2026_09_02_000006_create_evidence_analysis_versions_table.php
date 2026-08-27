<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_analysis_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_file_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            // 'ai_analysis' = snapshot of a completed AI run; 'reanalysis_requested' = auditor-triggered
            // re-analysis, snapshotting the result being superseded so it is never lost.
            $table->string('trigger_type');
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->text('ai_observations')->nullable();
            $table->text('ai_recommendations')->nullable();
            $table->json('ai_gaps')->nullable();
            $table->string('ai_analysis_status')->nullable();
            $table->boolean('ai_parse_fallback')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['evidence_file_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_analysis_versions');
    }
};
