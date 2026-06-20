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
            $table->string('hitl_status')->default('pending_review')->after('status');
            $table->text('customer_response')->nullable()->after('hitl_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evidence_files', function (Blueprint $table) {
            $table->dropColumn(['hitl_status', 'customer_response']);
        });
    }
};
