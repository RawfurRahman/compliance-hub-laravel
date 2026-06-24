<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop existing tables if they exist in correct dependency order
        Schema::dropIfExists('risk_register_tags');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('risk_acceptances');
        Schema::dropIfExists('risk_scores_history');
        Schema::dropIfExists('heatmap_config');
        Schema::dropIfExists('risk_comments');
        Schema::dropIfExists('risk_control_mappings');
        Schema::dropIfExists('risk_registers');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('controls');
        Schema::dropIfExists('activity_log');

        // 2. Safely ensure frameworks table exists
        if (!Schema::hasTable('frameworks')) {
            Schema::create('frameworks', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug', 100)->unique();
                $table->string('version', 50)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Safely ensure framework_controls table exists
        if (!Schema::hasTable('framework_controls')) {
            Schema::create('framework_controls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('framework_id')->constrained()->onDelete('cascade');
                $table->string('control_id');
                $table->string('domain');
                $table->text('requirement_description');
                $table->text('required_evidence')->nullable();
                $table->timestamps();
            });
        }

        // 4. Create controls table (internal control catalog)
        Schema::create('controls', function (Blueprint $table) {
            $table->id();
            $table->string('control_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('framework_id')->nullable()->constrained('frameworks')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Safely ensure departments table exists
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        // 6. Create assets table
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // Hardware, Software, Data, People, Process, etc.
            $table->decimal('value_bdt', 15, 2)->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 7. Safely ensure evidence table exists
        if (!Schema::hasTable('evidence')) {
            Schema::create('evidence', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('requirement_id');
                $table->string('name');
                $table->string('path');
                $table->string('url')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 8. Create risk_registers (main Risk Register table)
        Schema::create('risk_registers', function (Blueprint $table) {
            $table->id();
            
            // Backward compatibility / scoping fields
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('cascade');
            $table->foreignId('framework_control_id')->nullable()->constrained('framework_controls')->onDelete('set null');

            // Workbook exact schema
            $table->string('serial_no')->unique();
            $table->string('asset_process_service');
            $table->string('risk_owner');
            $table->date('risk_calculation_date');
            $table->decimal('asset_value_bdt', 15, 2);
            $table->json('threats'); // Array of threats e.g. ["Data Breach"]
            $table->unsignedTinyInteger('threat_level_t');
            $table->json('vulnerabilities'); // Array of vulnerabilities e.g. ["Unencrypted Data"]
            $table->unsignedTinyInteger('impact_confidentiality');
            $table->unsignedTinyInteger('impact_integrity');
            $table->unsignedTinyInteger('impact_availability');
            $table->text('existing_control');
            $table->unsignedTinyInteger('vulnerability_level_av');
            $table->unsignedTinyInteger('tv_t_av'); // threat_level_t + vulnerability_level_av
            $table->unsignedTinyInteger('likelihood_lh');
            $table->integer('risk_rating_avtvlh'); // vulnerability_level_av * tv_t_av * likelihood_lh
            $table->enum('measurement', ['Accepted', 'Not Accepted']);
            $table->text('proposed_control')->nullable();
            $table->text('communication')->nullable();
            $table->date('implementation_from')->nullable();
            $table->date('implementation_to')->nullable();
            $table->enum('implementation_status', ['Not Started', 'Pending', 'In Progress', 'Completed'])->default('Not Started');
            $table->unsignedTinyInteger('residual_tv');
            $table->unsignedTinyInteger('residual_lh');
            $table->integer('residual_rating'); // residual_tv * residual_lh
            $table->text('follow_up_note')->nullable();
            $table->string('category');
            $table->string('department');

            // Nullable & relationships
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('asset_id')->nullable()->constrained('assets')->onDelete('set null');
            $table->json('evidence_ids')->nullable(); // Array of evidence record IDs
            $table->enum('source', ['manual', 'import', 'n8n_ccm', 'vendor_response', 'ai_parse'])->default('manual');
            $table->string('legacy_source_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('custom_fields')->nullable();

            $table->timestamps();
            $table->softDeletes(); // includes deleted_at

            // Indexes for speed and lookups
            $table->index('serial_no');
            $table->index('category');
            $table->index('department');
            $table->index('measurement');
            $table->index('implementation_status');
        });

        // 9. Create risk_control_mappings
        Schema::create('risk_control_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_register_id')->constrained('risk_registers')->onDelete('cascade');
            $table->foreignId('framework_control_id')->nullable()->constrained('framework_controls')->onDelete('set null');
            $table->foreignId('control_id')->nullable()->constrained('controls')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 10. Create risk_scores_history
        Schema::create('risk_scores_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_register_id')->constrained('risk_registers')->onDelete('cascade');
            $table->unsignedTinyInteger('tv_score');
            $table->unsignedTinyInteger('lh_score');
            $table->integer('rating_score');
            $table->timestamp('recorded_at')->useCurrent();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
        });

        // 11. Create heatmap_config
        Schema::create('heatmap_config', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('critical_threshold')->default(128);
            $table->unsignedInteger('high_threshold')->default(84);
            $table->unsignedInteger('medium_threshold')->default(54);
            $table->unsignedInteger('low_threshold')->default(53);
            $table->timestamps();
        });

        // 12. Create risk_comments
        Schema::create('risk_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_register_id')->constrained('risk_registers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();
        });

        // 13. Create risk_acceptances
        Schema::create('risk_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_register_id')->constrained('risk_registers')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('justification');
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->timestamps();
        });

        // 14. Create integrations
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // e.g. n8n, jira, ServiceNow
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 15. Create tags
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // 16. Create risk_register_tags pivot
        Schema::create('risk_register_tags', function (Blueprint $table) {
            $table->foreignId('risk_register_id')->constrained('risk_registers')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');
            $table->primary(['risk_register_id', 'tag_id']);
        });

        // 17. Create activity_log (immutable)
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action');
            $table->text('description')->nullable();
            $table->json('details')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent(); // immutable: no updated_at/deleted_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('risk_register_tags');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('risk_acceptances');
        Schema::dropIfExists('risk_comments');
        Schema::dropIfExists('heatmap_config');
        Schema::dropIfExists('risk_scores_history');
        Schema::dropIfExists('risk_control_mappings');
        Schema::dropIfExists('risk_registers');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('controls');

        // NOTE: frameworks, framework_controls, departments, evidence are core and not dropped
    }
};
