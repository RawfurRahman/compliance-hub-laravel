<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pci_dss_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_pci_dss_detail_id')->constrained()->onDelete('cascade');
            $table->foreignId('pci_dss_requirement_id')->constrained()->onDelete('cascade');
            $table->string('assessment_finding')->nullable();
            $table->boolean('compensating_control')->default(false);
            $table->boolean('customized_approach')->default(false);
            $table->text('finding_description')->nullable();
            $table->json('assessor_responses')->nullable();
            $table->timestamps();

            $table->unique(['project_pci_dss_detail_id', 'pci_dss_requirement_id'], 'project_requirement_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pci_dss_findings');
    }
};
