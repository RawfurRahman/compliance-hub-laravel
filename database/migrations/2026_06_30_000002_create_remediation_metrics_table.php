<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * History table for remediation performance metrics (MTTR / SLA), suitable for
 * trend reporting. One row per (project, scope) per snapshot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remediation_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->string('scope', 100)->default('all'); // all | risk | control | category:<name>

            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('open_items')->default(0);
            $table->unsignedInteger('closed_items')->default(0);
            $table->unsignedInteger('overdue_count')->default(0);

            // Durations are stored in hours (decimal) for precise trend reporting.
            $table->decimal('mttr_hours', 12, 2)->nullable();
            $table->decimal('mtta_hours', 12, 2)->nullable(); // mean time to acknowledge
            $table->decimal('mtt_assign_hours', 12, 2)->nullable(); // mean time to assign
            $table->decimal('mttc_hours', 12, 2)->nullable(); // mean time to close

            $table->decimal('sla_breach_rate', 5, 2)->default(0); // %
            $table->decimal('closure_rate', 5, 2)->default(0);    // %

            $table->json('aging_buckets')->nullable(); // {"0-7":n,"8-30":n,...}
            $table->json('breakdown')->nullable();
            $table->timestamp('calculated_at')->useCurrent();
            $table->timestamps();

            $table->index(['project_id', 'scope']);
            $table->index('calculated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remediation_metrics');
    }
};
