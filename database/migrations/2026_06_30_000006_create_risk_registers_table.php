<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('risk_registers')) {
            return;
        }

        Schema::create('risk_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('framework_control_id')->nullable()->constrained()->nullOnDelete();
            $table->string('serial_no')->nullable();
            $table->string('asset_process_service')->nullable();
            $table->string('risk_owner')->nullable();
            $table->date('risk_calculation_date')->nullable();
            $table->decimal('asset_value_bdt', 20, 2)->nullable();
            $table->json('threats')->nullable();
            $table->integer('threat_level_t')->nullable();
            $table->json('vulnerabilities')->nullable();
            $table->integer('impact_confidentiality')->nullable();
            $table->integer('impact_integrity')->nullable();
            $table->integer('impact_availability')->nullable();
            $table->text('existing_control')->nullable();
            $table->integer('vulnerability_level_av')->nullable();
            $table->integer('tv_t_av')->nullable();
            $table->integer('likelihood_lh')->nullable();
            $table->integer('risk_rating_avtvlh')->nullable();
            $table->string('measurement')->nullable();
            $table->text('proposed_control')->nullable();
            $table->string('communication')->nullable();
            $table->date('implementation_from')->nullable();
            $table->date('implementation_to')->nullable();
            $table->string('implementation_status')->nullable();
            $table->string('lifecycle_status')->default('draft');
            $table->integer('residual_tv')->nullable();
            $table->integer('residual_lh')->nullable();
            $table->integer('residual_rating')->nullable();
            $table->text('follow_up_note')->nullable();
            $table->string('category')->nullable();
            $table->string('department')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->json('evidence_ids')->nullable();
            $table->string('source')->nullable();
            $table->string('legacy_source_id')->nullable();
            $table->foreignId('assessment_finding_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('custom_fields')->nullable();
            $table->integer('computed_tv')->nullable();
            $table->integer('computed_risk_rating')->nullable();
            $table->integer('computed_residual_rating')->nullable();
            $table->decimal('exposure_value', 20, 2)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_registers');
    }
};
