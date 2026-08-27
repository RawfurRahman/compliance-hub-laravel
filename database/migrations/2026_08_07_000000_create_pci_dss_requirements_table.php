<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pci_dss_requirements')) {
            return;
        }

        Schema::create('pci_dss_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('req_num');
            $table->text('req_description');
            $table->text('testing_procedures')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pci_dss_requirements');
    }
};
