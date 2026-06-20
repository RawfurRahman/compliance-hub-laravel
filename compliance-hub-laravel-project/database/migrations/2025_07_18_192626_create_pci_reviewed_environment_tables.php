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
        // Table for In-scope Third-Party Service Providers
        Schema::create('pci_tpsps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_pci_dss_detail_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('service_provided')->nullable();
            $table->timestamps();
        });

        // Table for In-scope Networks
        Schema::create('pci_networks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_pci_dss_detail_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('ip_range')->nullable();
            $table->timestamps();
        });

        // Table for In-scope Locations/Facilities
        Schema::create('pci_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_pci_dss_detail_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        // Table for In-scope System Component Types
        Schema::create('pci_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_pci_dss_detail_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable(); // Added this column to match the form
            $table->string('type')->nullable();
            // The 'count' column was removed as it is not used in the form.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pci_components');
        Schema::dropIfExists('pci_locations');
        Schema::dropIfExists('pci_networks');
        Schema::dropIfExists('pci_tpsps');
    }
};
