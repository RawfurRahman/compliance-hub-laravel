<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_control_mappings', function (Blueprint $table) {
            if (!Schema::hasColumn('risk_control_mappings', 'mapping_status')) {
                $table->string('mapping_status', 30)->default('suggested')->after('control_type');
            }
            if (!Schema::hasColumn('risk_control_mappings', 'confidence_score')) {
                $table->float('confidence_score', 5, 2)->nullable()->after('mapping_status');
            }
            if (!Schema::hasColumn('risk_control_mappings', 'mapped_by')) {
                $table->foreignId('mapped_by')->nullable()->after('confidence_score')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('risk_control_mappings', 'mapped_at')) {
                $table->timestamp('mapped_at')->nullable()->after('mapped_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('risk_control_mappings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mapped_by');
            $table->dropColumn(['mapping_status', 'confidence_score', 'mapped_at']);
        });
    }
};
