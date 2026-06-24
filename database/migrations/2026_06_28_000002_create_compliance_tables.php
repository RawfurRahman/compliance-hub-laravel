<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Framework Versions
        Schema::create('comp_framework_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('framework_id')->constrained('frameworks')->cascadeOnDelete();
            $table->string('version');
            $table->date('release_date')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['framework_id', 'version']);
        });

        // 2. Framework-Control Map
        Schema::create('comp_framework_control_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_id')->constrained('controls')->cascadeOnDelete();
            $table->foreignId('framework_control_id')->constrained('framework_controls')->cascadeOnDelete();
            $table->foreignId('framework_version_id')->nullable()->constrained('comp_framework_versions')->nullOnDelete();
            $table->string('mapping_type', 50)->default('direct');
            $table->text('mapping_notes')->nullable();
            $table->decimal('effectiveness_weight', 5, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['control_id', 'framework_control_id']);
        });

        // 3. Control Tests
        Schema::create('comp_control_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_id')->constrained('controls')->cascadeOnDelete();
            $table->foreignId('assessment_finding_id')->nullable()->constrained('assessment_findings')->nullOnDelete();
            $table->foreignId('tested_by')->constrained('users');
            $table->string('test_type', 50);
            $table->dateTime('test_date');
            $table->string('result', 50);
            $table->decimal('score', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->text('evidence_summary')->nullable();
            $table->foreignId('framework_version_id')->nullable()->constrained('comp_framework_versions')->nullOnDelete();
            $table->timestamps();
        });

        // 4. Monitoring Rules
        Schema::create('comp_monitoring_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_id')->nullable()->constrained('controls')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('rule_type', 50);
            $table->text('check_expression')->nullable();
            $table->string('schedule_cron', 100)->nullable();
            $table->decimal('threshold_value', 10, 2)->nullable();
            $table->string('severity', 20);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 5. Control Monitors
        Schema::create('comp_control_monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_id')->constrained('controls')->cascadeOnDelete();
            $table->foreignId('monitoring_rule_id')->nullable()->constrained('comp_monitoring_rules')->nullOnDelete();
            $table->foreignId('last_finding_id')->nullable()->constrained('assessment_findings')->nullOnDelete();
            $table->dateTime('last_run_at')->nullable();
            $table->dateTime('next_run_at')->nullable();
            $table->string('status', 20);
            $table->string('last_result', 20)->nullable();
            $table->integer('consecutive_failures')->default(0);
            $table->timestamps();
        });

        // 6. Audit Findings
        Schema::create('comp_audit_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('finding_reference')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('audit_date');
            $table->foreignId('auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('severity', 20);
            $table->string('status', 20);
            $table->foreignId('control_id')->nullable()->constrained('controls')->nullOnDelete();
            $table->foreignId('framework_control_id')->nullable()->constrained('framework_controls')->nullOnDelete();
            $table->text('remediation_plan')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 7. SLA Trackers
        Schema::create('comp_sla_trackers', function (Blueprint $table) {
            $table->id();
            $table->morphs('trackable');
            $table->string('sla_type', 50);
            $table->dateTime('deadline_at');
            $table->dateTime('breached_at')->nullable();
            $table->boolean('breach_notified')->default(false);
            $table->string('status', 20);
            $table->timestamps();
        });

        // 8. Compliance Snapshots
        Schema::create('comp_compliance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('snapshot_type', 20);
            $table->json('snapshot_data');
            $table->integer('total_controls')->default(0);
            $table->integer('compliant_count')->default(0);
            $table->integer('partial_count')->default(0);
            $table->integer('non_compliant_count')->default(0);
            $table->integer('waived_count')->default(0);
            $table->integer('overdue_count')->default(0);
            $table->integer('under_review_count')->default(0);
            $table->decimal('avg_remediation_time', 8, 2)->nullable();
            $table->date('snapshot_date');
            $table->timestamps();
        });

        // 9. Control Evidence
        Schema::create('comp_control_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_id')->constrained('controls')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->date('evidence_date')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_current')->default(true);
            $table->timestamps();
        });

        // 10. Alter assessment_findings — add compliance columns
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->string('source_type', 50)->nullable()->after('recommendation');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->string('compliance_state', 30)->nullable()->after('source_id');

            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropColumn(['source_type', 'source_id', 'compliance_state']);
        });

        Schema::dropIfExists('comp_control_evidence');
        Schema::dropIfExists('comp_compliance_snapshots');
        Schema::dropIfExists('comp_sla_trackers');
        Schema::dropIfExists('comp_audit_findings');
        Schema::dropIfExists('comp_control_monitors');
        Schema::dropIfExists('comp_monitoring_rules');
        Schema::dropIfExists('comp_control_tests');
        Schema::dropIfExists('comp_framework_control_map');
        Schema::dropIfExists('comp_framework_versions');
    }
};
