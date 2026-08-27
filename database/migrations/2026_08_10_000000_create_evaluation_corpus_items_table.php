<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluation_corpus_items')) {
            return;
        }

        Schema::create('evaluation_corpus_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('framework_control_id')->constrained('framework_controls')->cascadeOnDelete()->unique();
            $table->string('chapter')->nullable()->index();
            $table->string('ground_truth')->index();
            $table->string('evidence_type')->index();
            $table->string('evidence_name');
            $table->text('evidence_summary')->nullable();
            $table->text('truth_rationale');
            $table->json('expected_gaps');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_corpus_items');
    }
};
