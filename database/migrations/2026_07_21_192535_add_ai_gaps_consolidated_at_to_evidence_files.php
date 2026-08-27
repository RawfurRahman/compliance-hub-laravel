<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('evidence_files', function (Blueprint $table) {
            $table->timestamp('ai_gaps_consolidated_at')->nullable()->after('ai_analysis_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('evidence_files', function (Blueprint $table) {
            $table->dropColumn('ai_gaps_consolidated_at');
        });
    }
};
