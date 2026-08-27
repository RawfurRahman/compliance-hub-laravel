<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->string('gap_category')->nullable()->after('gap_description');
            $table->text('non_compliant_details')->nullable()->after('gap_category');
            $table->text('compliant_description')->nullable()->after('non_compliant_details');
            $table->text('remediation_plan')->nullable()->after('compliant_description');
            $table->string('evidence_provided')->nullable()->after('remediation_plan');
            $table->text('test_results')->nullable()->after('evidence_provided');
            $table->boolean('meets_standard')->default(false)->after('test_results');
            $table->text('auditor_notes')->nullable()->after('meets_standard');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->dropColumn([
                'gap_category', 'non_compliant_details', 'compliant_description',
                'remediation_plan', 'evidence_provided', 'test_results',
                'meets_standard', 'auditor_notes',
            ]);
        });
    }
};
