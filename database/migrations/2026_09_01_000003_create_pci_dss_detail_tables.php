<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_pci_dss_details')) {
            Schema::create('project_pci_dss_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('entity_name')->nullable();
                $table->date('assessment_date')->nullable();
                $table->string('ae_company_name')->nullable();
                $table->string('ae_dba')->nullable();
                $table->string('ae_mailing_address')->nullable();
                $table->string('ae_main_website')->nullable();
                $table->string('ae_contact_name')->nullable();
                $table->string('ae_contact_title')->nullable();
                $table->string('ae_phone_number')->nullable();
                $table->string('ae_email_address')->nullable();
                $table->string('assessor_company_name')->nullable();
                $table->string('assessor_mailing_address')->nullable();
                $table->string('assessor_website')->nullable();
                $table->string('assessor_lead_name')->nullable();
                $table->string('assessor_phone')->nullable();
                $table->string('assessor_email')->nullable();
                $table->string('assessor_certificate_number')->nullable();
                $table->date('date_of_report')->nullable();
                $table->date('date_assessment_ended')->nullable();
                $table->boolean('remote_assessment')->nullable();
                $table->text('remote_justification')->nullable();
                $table->boolean('additional_services')->nullable();
                $table->text('additional_services_desc')->nullable();
                $table->boolean('subcontractors_used')->nullable();
                $table->text('subcontractor_list')->nullable();
                $table->text('overall_assessment_result')->nullable();
                $table->json('summary_findings')->nullable();
                $table->text('business_overview_desc')->nullable();
                $table->json('payment_channels')->nullable();
                $table->text('scope_validation_activities')->nullable();
                $table->text('scope_excluded_areas')->nullable();
                $table->text('scope_reduction_factors')->nullable();
                $table->text('saq_eligibility')->nullable();
                $table->boolean('segmentation_used')->nullable();
                $table->text('segmentation_desc')->nullable();
                $table->boolean('pci_ssc_products_used')->nullable();
                $table->text('network_diagrams_desc')->nullable();
                $table->text('account_dataflow_diagrams_desc')->nullable();
                $table->text('storage_account_data_desc')->nullable();
                $table->json('assessment_activities')->nullable();
                $table->json('overall_findings')->nullable();
                $table->timestamps();

                $table->index('project_id');
            });
        }

        if (! Schema::hasTable('pci_components')) {
            Schema::create('pci_components', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_pci_dss_detail_id')->constrained('project_pci_dss_details')->cascadeOnDelete();
                $table->string('name')->nullable();
                $table->string('type')->nullable();
                $table->string('component_name')->nullable();
                $table->string('component_type')->nullable();
                $table->timestamps();

                $table->index('project_pci_dss_detail_id');
            });
        }

        if (! Schema::hasTable('pci_dss_findings')) {
            Schema::create('pci_dss_findings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_pci_dss_detail_id')->constrained('project_pci_dss_details')->cascadeOnDelete();
                $table->foreignId('pci_dss_requirement_id')->nullable()->constrained('pci_dss_requirements')->nullOnDelete();
                $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('assessment_finding')->nullable();
                $table->boolean('compensating_control')->nullable();
                $table->boolean('customized_approach')->nullable();
                $table->text('finding_description')->nullable();
                $table->json('assessor_responses')->nullable();
                $table->boolean('is_applicable')->default(true);
                $table->text('required_documents')->nullable();
                $table->timestamps();

                $table->index('project_pci_dss_detail_id');
                $table->index('pci_dss_requirement_id');
                $table->index('assessment_finding');
            });
        }

        if (! Schema::hasTable('pci_external_scans')) {
            Schema::create('pci_external_scans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_pci_dss_detail_id')->constrained('project_pci_dss_details')->cascadeOnDelete();
                $table->date('scan_date')->nullable();
                $table->string('result')->nullable();
                $table->boolean('initial_assessment')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pci_internal_scans')) {
            Schema::create('pci_internal_scans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_pci_dss_detail_id')->constrained('project_pci_dss_details')->cascadeOnDelete();
                $table->date('scan_date')->nullable();
                $table->string('result')->nullable();
                $table->boolean('initial_assessment')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pci_locations')) {
            Schema::create('pci_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_pci_dss_detail_id')->constrained('project_pci_dss_details')->cascadeOnDelete();
                $table->string('name')->nullable();
                $table->string('address')->nullable();
                $table->string('location_name')->nullable();
                $table->string('location_address')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pci_networks')) {
            Schema::create('pci_networks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_pci_dss_detail_id')->constrained('project_pci_dss_details')->cascadeOnDelete();
                $table->string('name')->nullable();
                $table->string('ip_range')->nullable();
                $table->string('network_name')->nullable();
                $table->string('network_type')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pci_ssc_products')) {
            Schema::create('pci_ssc_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_pci_dss_detail_id')->constrained('project_pci_dss_details')->cascadeOnDelete();
                $table->string('product_name');
                $table->string('version')->nullable();
                $table->string('vendor')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pci_tpsps')) {
            Schema::create('pci_tpsps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_pci_dss_detail_id')->constrained('project_pci_dss_details')->cascadeOnDelete();
                $table->string('name')->nullable();
                $table->string('service_provided')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pci_gap_assessments')) {
            Schema::create('pci_gap_assessments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->text('requirement_text')->nullable();
                $table->boolean('is_section_header')->default(false);
                $table->string('status')->nullable();
                $table->text('na_explanation')->nullable();
                $table->date('milestone_date')->nullable();
                $table->text('comments')->nullable();
                $table->timestamps();

                $table->index('project_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pci_gap_assessments');
        Schema::dropIfExists('pci_tpsps');
        Schema::dropIfExists('pci_ssc_products');
        Schema::dropIfExists('pci_networks');
        Schema::dropIfExists('pci_locations');
        Schema::dropIfExists('pci_internal_scans');
        Schema::dropIfExists('pci_external_scans');
        Schema::dropIfExists('pci_dss_findings');
        Schema::dropIfExists('pci_components');
        Schema::dropIfExists('project_pci_dss_details');
    }
};