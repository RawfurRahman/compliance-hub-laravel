<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maturity_score_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date');
            $table->enum('dimension', [
                'risk_management',
                'control_design',
                'remediation_velocity',
                'evidence_audit',
                'composite',
            ]);
            $table->decimal('score_value', 3, 1); // 1.0 to 5.0
            $table->integer('sample_size')->default(0);
            $table->text('calculation_notes')->nullable();
            $table->timestamps();

            $table->index(['snapshot_date', 'dimension']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maturity_score_snapshots');
    }
};
