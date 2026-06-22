<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The remediation velocity metric requires a remediation deadline per
     * finding. The original assessment_findings table has no date column, so
     * we add a nullable due_date here.
     */
    public function up(): void
    {
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('recommendation');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};
