<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing tables to ensure a clean state
        Schema::dropIfExists('assessment_findings');
        Schema::dropIfExists('project_assessments');

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->enum('framework', ['ISO 27001', 'HITRUST']);
            $table->enum('assessment_type', ['Gap', 'Final']);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('cloned_from_id')->nullable()->constrained('assessments')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('assessment_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->onDelete('cascade');
            $table->string('serial_no');
            $table->enum('status', ['Open', 'Closed', 'In Progress'])->default('Open');
            $table->string('observation_title');
            $table->enum('risk_rating', ['High', 'Medium', 'Low', 'None'])->default('None');
            $table->longText('current_state')->nullable();
            $table->longText('gap_description')->nullable();
            $table->longText('impact_risk')->nullable();
            $table->longText('recommendation')->nullable();
            $table->longText('standard_reference')->nullable();
            $table->boolean('is_compliant')->default(false);
            $table->foreignId('cloned_from_finding_id')->nullable()->constrained('assessment_findings')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_findings');
        Schema::dropIfExists('assessments');
    }
};
