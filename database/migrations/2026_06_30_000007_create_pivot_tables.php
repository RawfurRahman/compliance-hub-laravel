<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // project_user pivot
        if (! Schema::hasTable('project_user')) {
            Schema::create('project_user', function (Blueprint $table) {
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->primary(['project_id', 'user_id']);
            });
        }

        // user_roles pivot
        if (! Schema::hasTable('user_roles')) {
            Schema::create('user_roles', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->primary(['user_id', 'role_id']);
            });
        }

        // meeting_user pivot
        if (! Schema::hasTable('meeting_user')) {
            Schema::create('meeting_user', function (Blueprint $table) {
                $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->primary(['meeting_id', 'user_id']);
            });
        }

        // assessment_finding_evidence pivot
        if (! Schema::hasTable('assessment_finding_evidence')) {
            Schema::create('assessment_finding_evidence', function (Blueprint $table) {
                $table->foreignId('assessment_finding_id')->constrained()->cascadeOnDelete();
                $table->foreignId('evidence_id')->constrained()->cascadeOnDelete();
                $table->primary(['assessment_finding_id', 'evidence_id']);
            });
        }

        // gap_evidence_links pivot
        if (! Schema::hasTable('gap_evidence_links')) {
            Schema::create('gap_evidence_links', function (Blueprint $table) {
                $table->foreignId('gap_control_id')->constrained('gap_controls')->cascadeOnDelete();
                $table->foreignId('evidence_file_id')->constrained('evidence_files')->cascadeOnDelete();
                $table->primary(['gap_control_id', 'evidence_file_id']);
            });
        }

        // risk_register_tags pivot
        if (! Schema::hasTable('risk_register_tags')) {
            Schema::create('risk_register_tags', function (Blueprint $table) {
                $table->foreignId('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
                $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
                $table->primary(['risk_register_id', 'tag_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_register_tags');
        Schema::dropIfExists('gap_evidence_links');
        Schema::dropIfExists('assessment_finding_evidence');
        Schema::dropIfExists('meeting_user');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('project_user');
    }
};
