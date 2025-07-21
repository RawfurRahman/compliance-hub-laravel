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
        Schema::table('users', function (Blueprint $table) {
            // Add the new 'username' column.
            // We place it after the 'id' column for neatness.
            // It must be unique as users will use it to log in.
            $table->string('username')->after('id')->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // This defines how to "undo" the migration.
            // It will drop the 'username' column if we ever need to roll back.
            $table->dropColumn('username');
        });
    }
};
