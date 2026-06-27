<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_assessments')) {
            Schema::table('vendor_assessments', function (Blueprint $table) {
                if (!Schema::hasColumn('vendor_assessments', 'ai_summary')) {
                    $table->text('ai_summary')->nullable()->after('findings_summary');
                }
                if (!Schema::hasColumn('vendor_assessments', 'ai_summary_generated_at')) {
                    $table->timestamp('ai_summary_generated_at')->nullable()->after('ai_summary');
                }
            });
        }

        if (Schema::hasTable('vendor_questionnaire_responses')) {
            Schema::table('vendor_questionnaire_responses', function (Blueprint $table) {
                if (!Schema::hasColumn('vendor_questionnaire_responses', 'ai_suggested_answer')) {
                    $table->text('ai_suggested_answer')->nullable()->after('comments');
                }
                if (!Schema::hasColumn('vendor_questionnaire_responses', 'needs_vendor_review')) {
                    $table->boolean('needs_vendor_review')->default(false)->after('ai_suggested_answer');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vendor_assessments')) {
            Schema::table('vendor_assessments', function (Blueprint $table) {
                $table->dropColumn(['ai_summary', 'ai_summary_generated_at']);
            });
        }
        if (Schema::hasTable('vendor_questionnaire_responses')) {
            Schema::table('vendor_questionnaire_responses', function (Blueprint $table) {
                $table->dropColumn(['ai_suggested_answer', 'needs_vendor_review']);
            });
        }
    }
};
