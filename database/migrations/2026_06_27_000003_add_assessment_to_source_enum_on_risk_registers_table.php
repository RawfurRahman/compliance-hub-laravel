<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE risk_registers MODIFY COLUMN source ENUM('manual', 'import', 'n8n_ccm', 'vendor_response', 'ai_parse', 'assessment') NOT NULL DEFAULT 'manual'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE risk_registers MODIFY COLUMN source ENUM('manual', 'import', 'n8n_ccm', 'vendor_response', 'ai_parse') NOT NULL DEFAULT 'manual'");
        }
    }
};
