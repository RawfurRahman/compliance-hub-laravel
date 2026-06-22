<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->boolean('is_applicable')->default(true)->after('is_compliant');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->dropColumn('is_applicable');
        });
    }
};
