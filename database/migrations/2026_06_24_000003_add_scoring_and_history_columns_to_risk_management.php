<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add columns to risk_control_mappings
        Schema::table('risk_control_mappings', function (Blueprint $table) {
            $table->unsignedInteger('effectiveness')->default(0)->after('control_id'); // 0 to 100%
            $table->string('control_type')->nullable()->after('effectiveness'); // Preventive, Detective, Corrective
        });

        // 2. Add columns to risk_scores_history
        Schema::table('risk_scores_history', function (Blueprint $table) {
            $table->unsignedTinyInteger('threat_level_t')->nullable()->after('rating_score');
            $table->unsignedTinyInteger('vulnerability_level_av')->nullable()->after('threat_level_t');
            $table->decimal('control_effectiveness', 5, 2)->nullable()->after('vulnerability_level_av'); // 0.00 to 100.00
            $table->string('formula_version')->nullable()->after('control_effectiveness');
            $table->unsignedTinyInteger('residual_tv')->nullable()->after('formula_version');
            $table->unsignedTinyInteger('residual_lh')->nullable()->after('residual_tv');
            $table->integer('residual_rating')->nullable()->after('residual_lh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('risk_control_mappings', function (Blueprint $table) {
            $table->dropColumn(['effectiveness', 'control_type']);
        });

        Schema::table('risk_scores_history', function (Blueprint $table) {
            $table->dropColumn([
                'threat_level_t',
                'vulnerability_level_av',
                'control_effectiveness',
                'formula_version',
                'residual_tv',
                'residual_lh',
                'residual_rating',
            ]);
        });
    }
};
