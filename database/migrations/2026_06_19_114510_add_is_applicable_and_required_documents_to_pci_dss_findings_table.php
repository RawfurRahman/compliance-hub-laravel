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
        Schema::table('pci_dss_findings', function (Blueprint $table) {
            $table->boolean('is_applicable')->default(true)->after('assessor_responses');
            $table->string('required_documents')->nullable()->after('is_applicable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pci_dss_findings', function (Blueprint $table) {
            $table->dropColumn(['is_applicable', 'required_documents']);
        });
    }
};
