<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_assessments')) {
            return;
        }

        Schema::create('vendor_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('third_party_vendors')->cascadeOnDelete();
            $table->foreignId('assessor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assessment_type');
            $table->date('assessment_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('overall_score', 10, 2)->nullable();
            $table->string('risk_rating')->nullable();
            $table->text('findings_summary')->nullable();
            $table->boolean('remediation_required')->default(false);
            $table->date('remediation_deadline')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_assessments');
    }
};
