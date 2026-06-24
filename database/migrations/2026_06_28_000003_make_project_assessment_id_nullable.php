<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->foreignId('project_assessment_id')->nullable()->change();
            $table->foreignId('framework_control_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->foreignId('project_assessment_id')->nullable(false)->change();
            $table->foreignId('framework_control_id')->nullable(false)->change();
        });
    }
};
