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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('gap_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->string('control_id');
            $table->text('requirement_description');
            $table->text('required_evidence')->nullable();
            $table->enum('status', ['Pending', 'Done'])->default('Pending');
            $table->timestamps();
        });

        Schema::create('gap_evidence_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gap_control_id')->constrained('gap_controls')->onDelete('cascade');
            $table->foreignId('evidence_file_id')->constrained('evidence_files')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gap_evidence_links');
        Schema::dropIfExists('gap_controls');
        Schema::dropIfExists('departments');
    }
};
