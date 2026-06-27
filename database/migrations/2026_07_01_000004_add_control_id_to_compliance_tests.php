<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comp_compliance_tests', function (Blueprint $table) {
            $table->foreignId('control_id')->nullable()->after('integration_id')
                ->constrained('controls')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('comp_compliance_tests', function (Blueprint $table) {
            $table->dropForeign(['control_id']);
            $table->dropColumn('control_id');
        });
    }
};
