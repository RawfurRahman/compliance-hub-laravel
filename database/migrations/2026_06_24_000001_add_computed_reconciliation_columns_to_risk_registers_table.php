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
        Schema::table('risk_registers', function (Blueprint $table) {
            $table->unsignedTinyInteger('computed_tv')->nullable()->after('tv_t_av');
            $table->integer('computed_risk_rating')->nullable()->after('risk_rating_avtvlh');
            $table->integer('computed_residual_rating')->nullable()->after('residual_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('risk_registers', function (Blueprint $table) {
            $table->dropColumn(['computed_tv', 'computed_risk_rating', 'computed_residual_rating']);
        });
    }
};
