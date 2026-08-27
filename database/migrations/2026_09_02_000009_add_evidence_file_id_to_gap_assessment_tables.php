<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iso_gap_assessments', function (Blueprint $table) {
            $table->foreignId('evidence_file_id')->nullable()->after('project_id')
                ->constrained('evidence_files')->nullOnDelete();
        });

        Schema::table('pci_gap_assessments', function (Blueprint $table) {
            $table->foreignId('evidence_file_id')->nullable()->after('project_id')
                ->constrained('evidence_files')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pci_gap_assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evidence_file_id');
        });

        Schema::table('iso_gap_assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evidence_file_id');
        });
    }
};
