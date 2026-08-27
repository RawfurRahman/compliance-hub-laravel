<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidence_files', function (Blueprint $table) {
            if (! Schema::hasColumn('evidence_files', 'scan_ms')) {
                $table->unsignedInteger('scan_ms')->nullable()->after('ai_gaps_consolidated_at');
            }
            if (! Schema::hasColumn('evidence_files', 'analysis_ms')) {
                $table->unsignedInteger('analysis_ms')->nullable()->after('scan_ms');
            }
            if (! Schema::hasColumn('evidence_files', 'total_ms')) {
                $table->unsignedInteger('total_ms')->nullable()->after('analysis_ms');
            }
        });
    }

    public function down(): void
    {
        Schema::table('evidence_files', function (Blueprint $table) {
            $table->dropColumn(['scan_ms', 'analysis_ms', 'total_ms']);
        });
    }
};
