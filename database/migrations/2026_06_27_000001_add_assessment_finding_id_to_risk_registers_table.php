<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_registers', function (Blueprint $table) {
            $table->foreignId('assessment_finding_id')
                ->nullable()
                ->after('legacy_source_id')
                ->constrained('assessment_findings')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('risk_registers', function (Blueprint $table) {
            $table->dropForeign(['assessment_finding_id']);
            $table->dropColumn('assessment_finding_id');
        });
    }
};
