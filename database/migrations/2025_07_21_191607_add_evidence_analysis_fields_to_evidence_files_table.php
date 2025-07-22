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
            // Add columns for file scanning
            $table->string('scan_status')->default('pending')->after('mime_type');
            $table->json('scan_details')->nullable()->after('scan_status');

            // Add columns for AI analysis
            $table->text('ai_observations')->nullable()->after('scan_details');
            $table->text('ai_recommendations')->nullable()->after('ai_observations');
            $table->string('ai_analysis_status')->default('pending')->after('ai_recommendations');
            $table->unsignedBigInteger('ai_analysis_approved_by')->nullable()->after('ai_analysis_status');
            $table->timestamp('ai_analysis_approved_at')->nullable()->after('ai_analysis_approved_by');

            // Add foreign key constraint for approved_by user
            $table->foreign('ai_analysis_approved_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null'); // Set to null if the approving user is deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evidence_files', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['ai_analysis_approved_by']);

            // Drop columns in reverse order of creation
            $table->dropColumn([
                'scan_status',
                'scan_details',
                'ai_observations',
                'ai_recommendations',
                'ai_analysis_status',
                'ai_analysis_approved_by',
                'ai_analysis_approved_at',
            ]);
        });
    }
};

