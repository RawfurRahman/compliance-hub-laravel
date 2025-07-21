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
        Schema::create('pci_external_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_pci_dss_detail_id')->constrained()->onDelete('cascade');
            $table->date('scan_date')->nullable();
            $table->string('result')->nullable();
            $table->boolean('initial_assessment')->default(false);
            $table->timestamps();
        });

        Schema::create('pci_internal_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_pci_dss_detail_id')->constrained()->onDelete('cascade');
            $table->date('scan_date')->nullable();
            $table->string('result')->nullable();
            $table->boolean('initial_assessment')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pci_internal_scans');
        Schema::dropIfExists('pci_external_scans');
    }
};
