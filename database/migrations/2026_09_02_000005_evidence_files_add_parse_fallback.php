<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evidence_files') && ! Schema::hasColumn('evidence_files', 'ai_parse_fallback')) {
            Schema::table('evidence_files', function (Blueprint $table) {
                $table->boolean('ai_parse_fallback')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('evidence_files') && Schema::hasColumn('evidence_files', 'ai_parse_fallback')) {
            Schema::table('evidence_files', function (Blueprint $table) {
                $table->dropColumn('ai_parse_fallback');
            });
        }
    }
};
