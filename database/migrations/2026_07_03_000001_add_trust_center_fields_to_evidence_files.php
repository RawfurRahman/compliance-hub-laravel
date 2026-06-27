<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('evidence_files')) {
            return;
        }

        Schema::table('evidence_files', function (Blueprint $table) {
            if (!Schema::hasColumn('evidence_files', 'trust_center_id')) {
                $table->foreignId('trust_center_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete()
                    ->after('framework_control_id');
            }
            if (!Schema::hasColumn('evidence_files', 'is_publicly_listed')) {
                $table->boolean('is_publicly_listed')
                    ->default(false)
                    ->after('trust_center_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('evidence_files')) {
            return;
        }

        Schema::table('evidence_files', function (Blueprint $table) {
            $table->dropForeign(['trust_center_id']);
            $table->dropColumn(['trust_center_id', 'is_publicly_listed']);
        });
    }
};
