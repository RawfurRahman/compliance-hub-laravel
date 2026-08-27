<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_registers', function (Blueprint $table) {
            $table->foreignId('observation_id')->nullable()->after('assessment_finding_id')
                ->constrained('observations')->nullOnDelete();
        });

        Schema::table('observations', function (Blueprint $table) {
            $table->foreignId('risk_register_id')->nullable()->after('final_assessment_finding_id')
                ->constrained('risk_registers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('risk_register_id');
        });

        Schema::table('risk_registers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('observation_id');
        });
    }
};
