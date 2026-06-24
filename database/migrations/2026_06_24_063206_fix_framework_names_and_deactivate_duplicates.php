<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Fix duplicate / misnamed frameworks ────────────────────────────

        // HITRUST v9.4 → "HITRUST CSF v 11.8.0"
        DB::table('frameworks')
            ->where('slug', 'hitrust')
            ->update(['name' => 'HITRUST CSF', 'version' => 'v 11.8.0']);

        // PCI DSS v4.0 v4.0 → "PCI DSS v 4.0" (duplicate of active pci_dss)
        DB::table('frameworks')
            ->where('slug', 'pci_dss_v4')
            ->update(['name' => 'PCI DSS', 'version' => 'v 4.0', 'is_active' => false]);

        // SWIFT CSCF 2026 2026 → "SWIFT CSCF 2026" (remove version suffix from name)
        DB::table('frameworks')
            ->where('slug', 'swift_cscf_2026')
            ->update(['name' => 'SWIFT CSCF', 'version' => '2026']);

        // BB ICT Guidelines — internal control container, hide from sidebar
        DB::table('frameworks')
            ->where('slug', 'bb_ict')
            ->update(['is_active' => false]);

        // SWIFT CSP 2024 — no projects use it, and the active SWIFT framework is swift_cscf_2026
        $swiftCspInUse = DB::table('projects')->where('module_type', 'swift_csp')->exists();
        if (! $swiftCspInUse) {
            DB::table('frameworks')->where('slug', 'swift_csp')->update(['is_active' => false]);
        }

        // Ensure iso_27001_2022 remains deactivated
        DB::table('frameworks')
            ->where('slug', 'iso_27001_2022')
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        DB::table('frameworks')->where('slug', 'hitrust')
            ->update(['name' => 'HITRUST', 'version' => 'v9.4']);

        DB::table('frameworks')->where('slug', 'pci_dss_v4')
            ->update(['name' => 'PCI DSS v4.0', 'version' => 'v4.0', 'is_active' => true]);

        DB::table('frameworks')->where('slug', 'swift_cscf_2026')
            ->update(['name' => 'SWIFT CSCF 2026', 'version' => '2026']);

        DB::table('frameworks')->where('slug', 'bb_ict')
            ->update(['is_active' => true]);

        DB::table('frameworks')->where('slug', 'swift_csp')
            ->update(['is_active' => true]);

        DB::table('frameworks')->where('slug', 'iso_27001_2022')
            ->update(['is_active' => true]);
    }
};
