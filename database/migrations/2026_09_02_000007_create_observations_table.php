<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_finding_id')->constrained('assessment_findings')->cascadeOnDelete();
            $table->foreignId('final_assessment_finding_id')->nullable()->constrained('assessment_findings')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->text('gap')->nullable();
            $table->text('risk_impact')->nullable();
            $table->text('recommendation')->nullable();
            $table->text('management_response')->nullable();
            $table->text('corrective_action')->nullable();

            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('raised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('target_date')->nullable();

            // Open -> In Progress -> Resolved -> Closed; Overdue is derived from target_date, not stored.
            $table->string('status')->default('Open');
            $table->timestamp('sent_to_final_assessment_at')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
};
