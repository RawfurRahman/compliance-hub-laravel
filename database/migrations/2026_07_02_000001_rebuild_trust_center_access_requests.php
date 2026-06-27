<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('trust_center_access_requests');

        Schema::create('trust_center_access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trust_center_id')->constrained()->cascadeOnDelete();
            $table->string('requester_name');
            $table->string('requester_email');
            $table->string('requester_company')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('Pending');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trust_center_access_requests');
    }
};
