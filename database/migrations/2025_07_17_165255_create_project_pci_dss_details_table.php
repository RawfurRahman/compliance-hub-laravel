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
        Schema::create('project_pci_dss_details', function (Blueprint $table) {
            $table->id();
            // Link to the main projects table
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            // Part I: Assessment Overview
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

            $table->boolean('remote_assessment')->default(false);
            $table->text('remote_justification')->nullable();

            $table->boolean('additional_services')->default(false);
            $table->text('additional_services_desc')->nullable();

            $table->boolean('subcontractors_used')->default(false);
            $table->text('subcontractor_list')->nullable();

            $table->string('overall_assessment_result')->nullable();

            $table->json('summary_findings')->nullable();

            $table->text('business_overview_desc')->nullable();
            $table->json('payment_channels')->nullable();

            $table->text('scope_validation_activities')->nullable();
            $table->text('scope_excluded_areas')->nullable();
            $table->text('scope_reduction_factors')->nullable();
            $table->text('saq_eligibility')->nullable();

            $table->boolean('segmentation_used')->default(false);
            $table->text('segmentation_desc')->nullable();

            $table->boolean('pci_ssc_products_used')->default(false);
            // Dynamic table data will be stored in separate tables linked to this one.

            $table->text('network_diagrams_desc')->nullable();
            $table->text('account_dataflow_diagrams_desc')->nullable();
            $table->text('storage_account_data_desc')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_pci_dss_details');
    }
};
