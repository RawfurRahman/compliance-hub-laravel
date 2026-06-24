<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('framework_controls', function (Blueprint $table) {
            if (!Schema::hasColumn('framework_controls', 'status')) {
                $table->string('status', 50)->nullable()->after('required_evidence');
            }
            if (!Schema::hasColumn('framework_controls', 'pci_dss_ref')) {
                $table->string('pci_dss_ref', 100)->nullable()->after('status');
            }
            if (!Schema::hasColumn('framework_controls', 'iso_ref')) {
                $table->string('iso_ref', 100)->nullable()->after('pci_dss_ref');
            }
            if (!Schema::hasColumn('framework_controls', 'bb_ict_ref')) {
                $table->string('bb_ict_ref', 100)->nullable()->after('iso_ref');
            }
            if (!Schema::hasColumn('framework_controls', 'swift_ref')) {
                $table->string('swift_ref', 100)->nullable()->after('bb_ict_ref');
            }
        });
    }

    public function down(): void
    {
        Schema::table('framework_controls', function (Blueprint $table) {
            $table->dropColumn(['status', 'pci_dss_ref', 'iso_ref', 'bb_ict_ref', 'swift_ref']);
        });
    }
};
