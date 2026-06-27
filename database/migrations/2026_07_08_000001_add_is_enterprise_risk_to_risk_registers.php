<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('risk_registers') && !Schema::hasColumn('risk_registers', 'is_enterprise_risk')) {
            Schema::table('risk_registers', function (Blueprint $table) {
                $table->boolean('is_enterprise_risk')->default(false)->after('exposure_value');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('risk_registers') && Schema::hasColumn('risk_registers', 'is_enterprise_risk')) {
            Schema::table('risk_registers', function (Blueprint $table) {
                $table->dropColumn('is_enterprise_risk');
            });
        }
    }
};
