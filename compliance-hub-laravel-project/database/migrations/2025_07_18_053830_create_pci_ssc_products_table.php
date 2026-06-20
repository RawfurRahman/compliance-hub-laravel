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
        Schema::create('pci_ssc_products', function (Blueprint $table) {
            $table->id();
            // Link to the pci dss details table
            $table->foreignId('project_pci_dss_detail_id')->constrained()->onDelete('cascade');
            $table->string('product_name')->nullable();
            $table->string('version')->nullable();
            $table->string('vendor')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pci_ssc_products');
    }
};
