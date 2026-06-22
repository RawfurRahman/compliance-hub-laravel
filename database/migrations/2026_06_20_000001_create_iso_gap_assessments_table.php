<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('iso_gap_assessments')) {
            return;
        }

        Schema::create('iso_gap_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('serial_no');
            $table->string('clause_reference');
            $table->string('observation_title');
            $table->enum('risk_rating', ['High', 'Medium', 'Low']);
            $table->text('current_state');
            $table->text('gap_description');
            $table->text('impact_risk');
            $table->text('recommendation');
            $table->enum('status', ['Open', 'Closed', 'In Progress'])->default('Open');
            $table->timestamps();

            $table->index(['project_id', 'risk_rating']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iso_gap_assessments');
    }
};
