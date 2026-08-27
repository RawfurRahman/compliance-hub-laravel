<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('comp_framework_control_map', 'framework_version_id')) {
            Schema::table('comp_framework_control_map', function (Blueprint $table) {
                $table->foreignId('framework_version_id')->nullable()->after('framework_control_id')->constrained('comp_framework_versions')->nullOnDelete();
                $table->index('framework_version_id');
            });
        }

        if (! Schema::hasColumn('comp_framework_control_map', 'created_by')) {
            Schema::table('comp_framework_control_map', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('effectiveness_weight')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasIndex('comp_framework_control_map', 'comp_framework_control_map_unique_index')) {
            Schema::table('comp_framework_control_map', function (Blueprint $table) {
                $table->unique(['control_id', 'framework_control_id'], 'comp_framework_control_map_unique_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('comp_framework_control_map', function (Blueprint $table) {
            $table->dropUnique('comp_framework_control_map_unique_index');
            $table->dropForeign(['framework_version_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['framework_version_id', 'created_by']);
        });
    }
};
