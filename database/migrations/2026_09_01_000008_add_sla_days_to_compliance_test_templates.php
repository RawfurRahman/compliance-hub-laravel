<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('comp_compliance_test_templates', 'sla_days')) {
            Schema::table('comp_compliance_test_templates', function (Blueprint $table) {
                $table->unsignedInteger('sla_days')->nullable()->after('test_type');
            });
        }
    }

    public function down(): void
    {
        Schema::table('comp_compliance_test_templates', function (Blueprint $table) {
            $table->dropColumn('sla_days');
        });
    }
};