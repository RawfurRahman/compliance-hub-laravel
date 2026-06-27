<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_exposure_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->string('scope', 100)->default('portfolio'); // portfolio | category:<name>
            $table->string('category', 100)->nullable();
            
            $table->unsignedInteger('risk_count')->default(0);
            $table->decimal('single_loss_expectancy', 20, 2)->default(0);
            $table->decimal('annualized_loss_expectancy', 20, 2)->default(0);
            $table->decimal('expected_remediation_cost', 20, 2)->default(0);
            $table->decimal('business_interruption_impact', 20, 2)->default(0);
            $table->decimal('portfolio_exposure', 20, 2)->default(0);
            $table->string('currency', 3)->default('BDT');
            
            $table->json('breakdown')->nullable(); // per-risk / per-category detail
            $table->timestamp('calculated_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['project_id', 'scope']);
            $table->index('calculated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_exposure_metrics');
    }
};