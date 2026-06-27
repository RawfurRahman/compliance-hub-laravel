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
        Schema::create('comp_compliance_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('team');
            $table->string('test_type');
            $table->unsignedInteger('sla_days')->nullable();
            $table->string('status');
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_due_at')->nullable();
            $table->foreignId('control_monitor_id')->nullable()->constrained('comp_control_monitors')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['next_due_at', 'status']);
            $table->index(['owner_user_id', 'team']);
        });

        Schema::create('comp_compliance_test_framework_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_test_id')->constrained('comp_compliance_tests')->cascadeOnDelete();
            $table->foreignId('framework_id')->constrained('frameworks')->cascadeOnDelete();
            $table->unsignedInteger('resources_in_scope_count')->nullable();
            $table->timestamps();
            $table->unique(['compliance_test_id', 'framework_id']);
        });

        Schema::create('comp_compliance_test_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_test_id')->constrained('comp_compliance_tests')->cascadeOnDelete();
            $table->text('failing_entity_description');
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['compliance_test_id', 'resolved_at']);
            $table->index(['detected_at', 'resolved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comp_compliance_test_failures');
        Schema::dropIfExists('comp_compliance_test_framework_links');
        Schema::dropIfExists('comp_compliance_tests');
    }
};
