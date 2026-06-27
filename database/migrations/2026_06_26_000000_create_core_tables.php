<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('otp', 6)->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('frameworks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('framework_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('framework_id')->constrained()->cascadeOnDelete();
            $table->string('control_id');
            $table->string('domain')->nullable();
            $table->text('requirement_description')->nullable();
            $table->text('required_evidence')->nullable();
            $table->string('status')->nullable();
            $table->string('pci_dss_ref')->nullable();
            $table->string('iso_ref')->nullable();
            $table->string('bb_ict_ref')->nullable();
            $table->string('swift_ref')->nullable();
            $table->timestamps();
        });

        Schema::create('controls', function (Blueprint $table) {
            $table->id();
            $table->string('control_code')->nullable();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('framework_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->string('status')->nullable();
            $table->decimal('effectiveness_score', 5, 2)->nullable();
            $table->foreignId('control_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('module_type');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('assessment_findings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_assessment_id')->nullable();
            $table->foreignId('framework_control_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->nullable();
            $table->string('risk_rating')->nullable();
            $table->text('observation')->nullable();
            $table->text('gap_description')->nullable();
            $table->text('impact')->nullable();
            $table->text('recommendation')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('is_compliant')->default(true);
            $table->boolean('is_applicable')->default(true);
            $table->foreignId('cloned_from_finding_id')->nullable()->constrained('assessment_findings')->nullOnDelete();
            $table->unsignedBigInteger('risk_register_id')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('compliance_state')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
        });

        Schema::create('comp_monitoring_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('rule_type');
            $table->text('check_expression')->nullable();
            $table->string('schedule_cron')->nullable();
            $table->decimal('threshold_value', 10, 2)->nullable();
            $table->string('severity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('risk_control_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_id')->constrained()->cascadeOnDelete();
            $table->foreignId('framework_control_id')->constrained()->cascadeOnDelete();
            $table->string('mapping_type')->nullable();
            $table->text('mapping_notes')->nullable();
            $table->decimal('effectiveness_weight', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('comp_framework_control_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_id')->constrained()->cascadeOnDelete();
            $table->foreignId('framework_control_id')->constrained()->cascadeOnDelete();
            $table->string('mapping_type')->nullable();
            $table->text('mapping_notes')->nullable();
            $table->decimal('effectiveness_weight', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('comp_control_monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitoring_rule_id')->constrained('comp_monitoring_rules')->cascadeOnDelete();
            $table->foreignId('last_finding_id')->nullable()->constrained('assessment_findings')->nullOnDelete();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->string('status')->default('active');
            $table->string('last_result')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comp_control_monitors');
        Schema::dropIfExists('comp_framework_control_map');
        Schema::dropIfExists('risk_control_mappings');
        Schema::dropIfExists('comp_monitoring_rules');
        Schema::dropIfExists('assessment_findings');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('controls');
        Schema::dropIfExists('framework_controls');
        Schema::dropIfExists('frameworks');
        Schema::dropIfExists('users');
    }
};
