<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluation_run_items')) {
            return;
        }

        Schema::create('evaluation_run_items', function (Blueprint $table) {
            $table->id();
            $table->string('run_key')->index();
            $table->unsignedInteger('item_order')->nullable();
            $table->foreignId('evaluation_corpus_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('evidence_file_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('framework_id')->nullable()->constrained('frameworks')->nullOnDelete();
            $table->foreignId('framework_control_id')->nullable()->constrained('framework_controls')->nullOnDelete();
            $table->string('chapter')->nullable();
            $table->string('control_id')->nullable();
            $table->string('evidence_type')->nullable();
            $table->string('evidence_name')->nullable();
            $table->string('ground_truth')->nullable();
            $table->string('predicted_verdict')->nullable();
            $table->boolean('verdict_match')->nullable();
            $table->unsignedInteger('scan_ms')->nullable();
            $table->unsignedInteger('analysis_ms')->nullable();
            $table->unsignedInteger('total_ms')->nullable();
            $table->string('scan_status')->nullable();
            $table->string('ai_analysis_status')->nullable();
            $table->unsignedInteger('gaps_count')->nullable();
            $table->timestamps();

            $table->index(['run_key', 'framework_id']);
            $table->index(['run_key', 'chapter']);
            $table->index(['run_key', 'verdict_match']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_run_items');
    }
};
