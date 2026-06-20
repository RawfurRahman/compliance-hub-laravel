<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['gap', 'final'])->default('gap');
            $table->enum('framework', ['iso_27001', 'hitrust'])->default('iso_27001');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            // Tracks which gap assessment this final assessment was cloned from
            $table->foreignId('cloned_from_id')->nullable()->constrained('project_assessments')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_assessments');
    }
};
