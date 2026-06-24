<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controls', function (Blueprint $table) {
            if (!Schema::hasColumn('controls', 'code')) {
                $table->string('code', 100)->nullable()->after('id');
            }
            if (!Schema::hasColumn('controls', 'title')) {
                $table->string('title', 255)->nullable()->after('code');
            }
            if (!Schema::hasColumn('controls', 'status')) {
                $table->string('status', 50)->default('active')->after('description');
            }
            if (!Schema::hasColumn('controls', 'effectiveness_score')) {
                $table->float('effectiveness_score', 5, 2)->default(0)->after('status');
            }
            if (!Schema::hasColumn('controls', 'control_owner_id')) {
                $table->foreignId('control_owner_id')->nullable()->after('effectiveness_score')
                    ->constrained('users')->nullOnDelete();
            }
        });

        // Migrate existing data: code = control_code, title = name
        DB::table('controls')->whereNull('code')->update([
            'code' => DB::raw('control_code'),
        ]);
        DB::table('controls')->whereNull('title')->update([
            'title' => DB::raw('name'),
        ]);
    }

    public function down(): void
    {
        Schema::table('controls', function (Blueprint $table) {
            $table->dropConstrainedForeignId('control_owner_id');
            $table->dropColumn(['code', 'title', 'status', 'effectiveness_score']);
        });
    }
};
