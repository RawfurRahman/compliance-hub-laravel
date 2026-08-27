<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evidence_files')) {
            return;
        }

        Schema::create('evidence_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pci_dss_requirement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('framework_control_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->string('scan_status')->default('pending');
            $table->json('scan_details')->nullable();
            $table->text('ai_observations')->nullable();
            $table->text('ai_recommendations')->nullable();
            $table->text('ai_gaps')->nullable();
            $table->string('ai_analysis_status')->default('pending');
            $table->foreignId('ai_analysis_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ai_analysis_approved_at')->nullable();
            $table->string('hitl_status')->default('pending_review');
            $table->text('customer_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_files');
    }
};
