<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_assessments')) {
            return;
        }

        Schema::create('project_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('framework_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // gap or final
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('overall_status')->default('in_progress');
            $table->foreignId('cloned_from_id')->nullable()->constrained('project_assessments')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_assessments');
    }
};
