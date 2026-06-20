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
        Schema::table('evidence', function (Blueprint $table) {
            // Add the project_id column after the 'id' column.
            // This column will link the evidence to a specific project.
            $table->foreignId('project_id')->after('id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            // This will remove the foreign key constraint and the column if you need to roll back.
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
