<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidence_files', function (Blueprint $table) {
            if (! Schema::hasColumn('evidence_files', 'analysis_report_data')) {
                $table->json('analysis_report_data')->nullable()->after('ai_gaps');
            }
            if (! Schema::hasColumn('evidence_files', 'tracker_status')) {
                $table->string('tracker_status')->default('pending')->after('hitl_status');
            }
            if (! Schema::hasColumn('evidence_files', 'gap_assessment_sent_at')) {
                $table->timestamp('gap_assessment_sent_at')->nullable()->after('tracker_status');
            }
            if (! Schema::hasColumn('evidence_files', 'final_report_flagged_at')) {
                $table->timestamp('final_report_flagged_at')->nullable()->after('gap_assessment_sent_at');
            }
            if (! Schema::hasColumn('evidence_files', 'risk_register_created_at')) {
                $table->timestamp('risk_register_created_at')->nullable()->after('final_report_flagged_at');
            }
            if (! Schema::hasColumn('evidence_files', 'report_section_data')) {
                $table->json('report_section_data')->nullable()->after('analysis_report_data');
            }

            $table->index('tracker_status');
        });

        Schema::create('evidence_workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_file_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_workflow_logs');

        Schema::table('evidence_files', function (Blueprint $table) {
            $table->dropIndex(['tracker_status']);
            $table->dropColumn([
                'analysis_report_data',
                'tracker_status',
                'gap_assessment_sent_at',
                'final_report_flagged_at',
                'risk_register_created_at',
                'report_section_data',
            ]);
        });
    }
};
