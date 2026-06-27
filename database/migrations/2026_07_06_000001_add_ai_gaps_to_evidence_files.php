<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evidence_files') && !Schema::hasColumn('evidence_files', 'ai_gaps')) {
            Schema::table('evidence_files', function (Blueprint $table) {
                $table->text('ai_gaps')->nullable()->after('ai_recommendations');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('evidence_files') && Schema::hasColumn('evidence_files', 'ai_gaps')) {
            Schema::table('evidence_files', function (Blueprint $table) {
                $table->dropColumn('ai_gaps');
            });
        }
    }
};
