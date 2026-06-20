<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('report_type'); // e.g., 'pci_dss_roc', 'pci_dss_aoc'
            $table->string('framework_slug')->nullable(); // e.g., 'pci_dss', 'iso_27001'
            $table->string('framework_version')->nullable(); // e.g., '4.0.1'
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('generated_at')->useCurrent();
            $table->json('exported_formats')->nullable(); // ['pdf', 'html', 'docx']
            $table->enum('status', ['draft', 'final', 'archived'])->default('draft');
            $table->json('metadata')->nullable(); // Custom data per report type
            $table->timestamps();

            $table->index(['project_id', 'report_type']);
            $table->index(['status', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
    }
};
