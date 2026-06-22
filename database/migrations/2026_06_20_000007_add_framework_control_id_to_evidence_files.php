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
        Schema::table('evidence_files', function (Blueprint $table) {
            // Make the PCI requirement column nullable to support agnostic frameworks
            $table->unsignedBigInteger('pci_dss_requirement_id')->nullable()->change();
            
            // Add framework_control_id as a foreign key to framework_controls
            $table->foreignId('framework_control_id')->nullable()->constrained('framework_controls')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evidence_files', function (Blueprint $table) {
            $table->dropForeign(['framework_control_id']);
            $table->dropColumn('framework_control_id');
            $table->unsignedBigInteger('pci_dss_requirement_id')->nullable(false)->change();
        });
    }
};
