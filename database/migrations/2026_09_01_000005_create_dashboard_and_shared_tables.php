<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dashboard_snapshots')) {
            Schema::create('dashboard_snapshots', function (Blueprint $table) {
                $table->id();
                $table->string('domain');
                $table->string('business_unit')->nullable();
                $table->string('framework')->nullable();
                $table->string('date_scope');
                $table->date('snapshot_date');
                $table->json('snapshot_data');
                $table->json('metadata')->nullable();
                $table->dateTime('snapped_at')->nullable();
                $table->timestamps();

                $table->index('domain');
                $table->index('business_unit');
                $table->index('framework');
                $table->index('date_scope');
                $table->index('snapshot_date');
            });
        }

        if (! Schema::hasTable('heatmap_config')) {
            Schema::create('heatmap_config', function (Blueprint $table) {
                $table->id();
                $table->integer('critical_threshold');
                $table->integer('high_threshold');
                $table->integer('medium_threshold');
                $table->integer('low_threshold');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('requirements')) {
            Schema::create('requirements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('req_num');
                $table->text('req_description');
                $table->text('long_description')->nullable();
                $table->timestamps();

                $table->index('project_id');
            });
        }

        if (! Schema::hasTable('evidence')) {
            Schema::create('evidence', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->unsignedBigInteger('requirement_id')->nullable();
                $table->string('name');
                $table->string('path');
                $table->string('url')->nullable();
                $table->string('description', 500)->nullable();
                $table->string('status')->nullable();
                $table->timestamps();

                $table->index('project_id');
                $table->index('requirement_id');
            });
        }

        if (! Schema::hasTable('gap_controls')) {
            Schema::create('gap_controls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('risk_departments')->nullOnDelete();
                $table->string('control_id');
                $table->text('requirement_description');
                $table->text('required_evidence')->nullable();
                $table->string('status');
                $table->timestamps();

                $table->index('project_id');
                $table->index('control_id');
            });
        }

        if (! Schema::hasTable('iso_gap_assessments')) {
            Schema::create('iso_gap_assessments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('serial_no')->nullable();
                $table->string('clause_reference')->nullable();
                $table->string('observation_title')->nullable();
                $table->string('risk_rating');
                $table->text('current_state')->nullable();
                $table->text('gap_description')->nullable();
                $table->text('impact_risk')->nullable();
                $table->text('recommendation')->nullable();
                $table->string('status');
                $table->timestamps();

                $table->index('project_id');
                $table->index('serial_no');
            });
        }

        if (! Schema::hasTable('meetings')) {
            Schema::create('meetings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->dateTime('scheduled_at');
                $table->string('status');
                $table->string('meeting_link', 500)->nullable();
                $table->json('additional_emails')->nullable();
                $table->timestamps();

                $table->index('scheduled_at');
                $table->index('created_by');
            });
        }

        if (! Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->text('message');
                $table->dateTime('read_at')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index('project_id');
                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('custom_report_templates')) {
            Schema::create('custom_report_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('name');
                $table->string('report_type');
                $table->json('sections')->nullable();
                $table->json('filters')->nullable();
                $table->timestamps();

                $table->index('project_id');
                $table->index('report_type');
            });
        }

        if (! Schema::hasTable('generated_reports')) {
            Schema::create('generated_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('report_type');
                $table->string('framework_slug')->nullable();
                $table->string('framework_version')->nullable();
                $table->dateTime('generated_at')->nullable();
                $table->json('exported_formats')->nullable();
                $table->string('status');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index('project_id');
                $table->index('report_type');
                $table->index('generated_at');
            });
        }

        if (! Schema::hasTable('report_schedules')) {
            Schema::create('report_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('report_type');
                $table->string('recipient_email', 500);
                $table->string('frequency');
                $table->string('format');
                $table->dateTime('last_sent_at')->nullable();
                $table->dateTime('next_run_at')->nullable();
                $table->timestamps();

                $table->index('next_run_at');
            });
        }

        if (! Schema::hasTable('required_document_lists')) {
            Schema::create('required_document_lists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('source_file_name')->nullable();
                $table->string('source_file_path')->nullable();
                $table->timestamps();

                $table->index('project_id');
            });
        }

        if (! Schema::hasTable('required_documents')) {
            Schema::create('required_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('required_document_list_id')->constrained('required_document_lists')->cascadeOnDelete();
                $table->string('document_name');
                $table->string('category')->nullable();
                $table->string('reference')->nullable();
                $table->text('description')->nullable();
                $table->integer('sort_order')->nullable();
                $table->timestamps();

                $table->index('required_document_list_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('required_documents');
        Schema::dropIfExists('required_document_lists');
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('generated_reports');
        Schema::dropIfExists('custom_report_templates');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('meetings');
        Schema::dropIfExists('iso_gap_assessments');
        Schema::dropIfExists('gap_controls');
        Schema::dropIfExists('evidence');
        Schema::dropIfExists('requirements');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('heatmap_config');
        Schema::dropIfExists('dashboard_snapshots');
    }
};
