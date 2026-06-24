<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->foreignId('risk_register_id')
                ->nullable()
                ->after('cloned_from_finding_id')
                ->constrained('risk_registers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->dropForeign(['risk_register_id']);
            $table->dropColumn('risk_register_id');
        });
    }
};
