<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_uploads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('source_type', 32)->default('direct_upload');
            $table->string('original_name');
            $table->string('display_name');
            $table->string('mime_type')->nullable();
            $table->string('extension', 32)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('storage_disk');
            $table->string('storage_path');
            $table->string('checksum', 64)->nullable();
            $table->string('file_kind', 32)->default('unknown');
            $table->string('status', 32)->default('uploaded');
            $table->string('detected_language', 16)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'file_kind']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_uploads');
    }
};
