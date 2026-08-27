<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('risk_control_mappings', 'risk_register_id')) {
            Schema::table('risk_control_mappings', function (Blueprint $table) {
                $table->foreignId('control_id')->nullable()->change();
                $table->foreignId('framework_control_id')->nullable()->change();

                $table->foreignId('risk_register_id')->nullable()->after('id')->constrained('risk_registers')->nullOnDelete();
                $table->decimal('effectiveness', 5, 2)->nullable()->after('control_id');
                $table->string('control_type')->nullable()->after('effectiveness');
                $table->text('notes')->nullable()->after('control_type');
                $table->string('mapping_status')->nullable()->after('notes');
                $table->float('confidence_score')->nullable()->after('mapping_status');
                $table->foreignId('mapped_by')->nullable()->after('confidence_score')->constrained('users')->nullOnDelete();
                $table->dateTime('mapped_at')->nullable()->after('mapped_by');

                $table->index('risk_register_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('risk_control_mappings', function (Blueprint $table) {
            $table->dropForeign(['risk_register_id']);
            $table->dropForeign(['mapped_by']);
            $table->dropColumn([
                'risk_register_id',
                'effectiveness',
                'control_type',
                'notes',
                'mapping_status',
                'confidence_score',
                'mapped_by',
                'mapped_at',
            ]);
        });
    }
};
