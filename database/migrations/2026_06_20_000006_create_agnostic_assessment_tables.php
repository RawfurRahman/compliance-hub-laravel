<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop legacy/placeholder tables if they exist
        Schema::dropIfExists('assessment_finding_evidence');
        Schema::dropIfExists('assessment_findings');
        Schema::dropIfExists('project_assessments');
        Schema::dropIfExists('framework_controls');
        Schema::dropIfExists('assessments'); // custom table from previous turn

        // 1. framework_controls
        Schema::create('framework_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('framework_id')->constrained()->onDelete('cascade');
            $table->string('control_id'); // e.g. A.5.1
            $table->string('domain'); // e.g. Information Security Policies
            $table->text('requirement_description');
            $table->text('required_evidence')->nullable();
            $table->timestamps();
        });

        // 2. project_assessments
        Schema::create('project_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('framework_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['Gap', 'Final'])->default('Gap');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('overall_status')->nullable();
            $table->foreignId('cloned_from_id')->nullable()->constrained('project_assessments')->onDelete('cascade');
            $table->timestamps();
        });

        // 3. assessment_findings
        Schema::create('assessment_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_assessment_id')->constrained()->onDelete('cascade');
            $table->foreignId('framework_control_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['Open', 'In Progress', 'Closed'])->default('Open');
            $table->enum('risk_rating', ['High', 'Medium', 'Low', 'None'])->default('None');
            $table->text('observation')->nullable();
            $table->text('gap_description')->nullable();
            $table->text('impact')->nullable();
            $table->text('recommendation')->nullable();
            $table->boolean('is_compliant')->default(false);
            $table->foreignId('cloned_from_finding_id')->nullable()->constrained('assessment_findings')->onDelete('cascade');
            $table->timestamps();
        });

        // 4. assessment_finding_evidence (pivot)
        Schema::create('assessment_finding_evidence', function (Blueprint $table) {
            $table->foreignId('assessment_finding_id')->constrained('assessment_findings')->onDelete('cascade');
            $table->foreignId('evidence_id')->constrained('evidence')->onDelete('cascade');
            $table->primary(['assessment_finding_id', 'evidence_id'], 'finding_evidence_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_finding_evidence');
        Schema::dropIfExists('assessment_findings');
        Schema::dropIfExists('project_assessments');
        Schema::dropIfExists('framework_controls');
    }
};
