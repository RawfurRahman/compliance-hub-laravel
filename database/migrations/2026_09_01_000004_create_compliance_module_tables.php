<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('comp_framework_versions')) {
            Schema::create('comp_framework_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('framework_id')->constrained('frameworks')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('version');
                $table->date('release_date')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['framework_id', 'version']);
                $table->index('is_active');
            });
        }

        if (! Schema::hasTable('comp_audit_findings')) {
            Schema::create('comp_audit_findings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('auditor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('control_id')->nullable()->constrained('controls')->nullOnDelete();
                $table->foreignId('framework_control_id')->nullable()->constrained('framework_controls')->nullOnDelete();
                $table->string('finding_reference')->unique();
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('audit_date');
                $table->string('severity', 20);
                $table->string('status', 20);
                $table->text('remediation_plan')->nullable();
                $table->date('due_date')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index('project_id');
                $table->index('status');
                $table->index('severity');
                $table->index('audit_date');
                $table->index('due_date');
            });
        }

        if (! Schema::hasTable('comp_compliance_snapshots')) {
            Schema::create('comp_compliance_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('snapshot_type', 20)->default('ondemand');
                $table->json('snapshot_data')->nullable();
                $table->integer('total_controls')->default(0);
                $table->integer('compliant_count')->default(0);
                $table->integer('partial_count')->default(0);
                $table->integer('non_compliant_count')->default(0);
                $table->integer('waived_count')->default(0);
                $table->integer('overdue_count')->default(0);
                $table->integer('under_review_count')->default(0);
                $table->decimal('avg_remediation_time', 12, 2)->nullable();
                $table->date('snapshot_date');
                $table->timestamps();

                $table->index('project_id');
                $table->index('snapshot_type');
                $table->index('snapshot_date');
            });
        }

        if (! Schema::hasTable('comp_control_evidence')) {
            Schema::create('comp_control_evidence', function (Blueprint $table) {
                $table->id();
                $table->foreignId('control_id')->constrained('controls')->cascadeOnDelete();
                $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->string('file_path')->nullable();
                $table->string('file_name')->nullable();
                $table->string('mime_type', 100)->nullable();
                $table->date('evidence_date')->nullable();
                $table->boolean('is_current')->default(true);
                $table->timestamps();

                $table->index('control_id');
                $table->index('is_current');
            });
        }

        if (! Schema::hasTable('comp_control_tests')) {
            Schema::create('comp_control_tests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('control_id')->constrained('controls')->cascadeOnDelete();
                $table->foreignId('assessment_finding_id')->nullable()->constrained('assessment_findings')->nullOnDelete();
                $table->foreignId('tested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('framework_version_id')->nullable()->constrained('comp_framework_versions')->nullOnDelete();
                $table->string('test_type', 50);
                $table->dateTime('test_date');
                $table->string('result', 20);
                $table->decimal('score', 12, 2)->nullable();
                $table->text('notes')->nullable();
                $table->text('evidence_summary')->nullable();
                $table->timestamps();

                $table->index('control_id');
                $table->index('assessment_finding_id');
                $table->index('framework_version_id');
                $table->index('test_date');
                $table->index('result');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comp_control_tests');
        Schema::dropIfExists('comp_control_evidence');
        Schema::dropIfExists('comp_compliance_snapshots');
        Schema::dropIfExists('comp_audit_findings');
        Schema::dropIfExists('comp_framework_versions');
    }
};
