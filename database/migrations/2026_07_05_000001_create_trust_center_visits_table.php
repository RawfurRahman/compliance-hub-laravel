<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trust_center_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trust_center_id')->constrained()->cascadeOnDelete();
            $table->timestamp('visited_at');
            $table->string('ip_hash', 64);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trust_center_visits');
    }
};
