<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_questionnaire_responses')) {
            return;
        }

        Schema::create('vendor_questionnaire_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('section')->nullable();
            $table->string('question_key')->nullable();
            $table->text('question_text');
            $table->text('response_text')->nullable();
            $table->string('response_type')->default('text');
            $table->decimal('score', 10, 2)->nullable();
            $table->decimal('max_score', 10, 2)->nullable();
            $table->string('evidence_file')->nullable();
            $table->boolean('is_compliant')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_questionnaire_responses');
    }
};
