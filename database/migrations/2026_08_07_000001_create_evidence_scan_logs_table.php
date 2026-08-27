<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_file_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_filename');
            $table->string('file_path')->nullable();
            $table->string('scan_status')->default('pending');
            $table->string('virus_name')->nullable();
            $table->json('scan_details')->nullable();
            $table->boolean('quarantined')->default(false);
            $table->string('quarantine_path')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->index('scan_status');
            $table->index('quarantined');
            $table->index('scanned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_scan_logs');
    }
};
