<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('required_document_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('source_file_name');
            $table->string('source_file_path');
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('required_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('required_document_list_id')->constrained()->cascadeOnDelete();
            $table->string('document_name');
            $table->string('category')->nullable();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['required_document_list_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('required_documents');
        Schema::dropIfExists('required_document_lists');
    }
};
