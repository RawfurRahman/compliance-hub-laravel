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
        Schema::create('pci_dss_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('req_num')->unique();
            $table->text('req_description');
            // This single JSON column will store both the testing procedures
            // and their corresponding reporting instructions together.
            $table->json('testing_procedures')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pci_dss_requirements');
    }
};
