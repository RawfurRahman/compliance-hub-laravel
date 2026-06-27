<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comp_compliance_tests', function (Blueprint $table) {
            $table->foreignId('integration_id')->nullable()->after('control_monitor_id')->constrained('integrations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('comp_compliance_tests', function (Blueprint $table) {
            $table->dropForeign(['integration_id']);
            $table->dropColumn('integration_id');
        });
    }
};
