<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_assessments')) {
            Schema::table('project_assessments', function (Blueprint $table) {
                $table->boolean('is_publicly_visible')->default(false)->after('overall_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_assessments')) {
            Schema::table('project_assessments', function (Blueprint $table) {
                $table->dropColumn('is_publicly_visible');
            });
        }
    }
};
