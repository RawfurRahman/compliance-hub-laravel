<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_assessment_id')->constrained()->onDelete('cascade');
            $table->string('serial_no');                          // e.g. 4.1.2
            $table->string('clause_reference');                   // e.g. ISO 27001:2022 Clause 5.2
            $table->string('observation_title');
            $table->enum('compliance_status', [
                'Compliant',
                'Partially Compliant',
                'Non-Compliant',
                'Not Applicable',
            ])->default('Non-Compliant');
            $table->enum('risk_rating', ['High', 'Medium', 'Low', 'None'])->default('Low');
            $table->longText('current_state')->nullable();        // Rich text (HTML)
            $table->longText('gap_description')->nullable();      // Rich text (HTML)
            $table->longText('impact_risk')->nullable();          // Rich text (HTML)
            $table->longText('recommendation')->nullable();       // Rich text (HTML)
            $table->enum('status', ['Open', 'In Progress', 'Closed'])->default('Open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_findings');
    }
};
