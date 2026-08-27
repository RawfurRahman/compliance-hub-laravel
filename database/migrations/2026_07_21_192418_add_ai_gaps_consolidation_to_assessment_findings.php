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
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->json('ai_gaps')->nullable()->after('compliance_state');
            $table->timestamp('ai_gaps_consolidated_at')->nullable()->after('ai_gaps');
            $table->foreignId('ai_gaps_consolidated_by')->nullable()->after('ai_gaps_consolidated_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->dropForeign(['ai_gaps_consolidated_by']);
            $table->dropColumn(['ai_gaps', 'ai_gaps_consolidated_at', 'ai_gaps_consolidated_by']);
        });
    }
};
