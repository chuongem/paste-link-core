<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_outputs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_upload_id')->constrained('ai_uploads')->cascadeOnDelete();
            $table->foreignId('ai_job_id')->constrained('ai_jobs')->cascadeOnDelete();
            $table->string('output_type', 32);
            $table->string('title');
            $table->longText('content_text')->nullable();
            $table->json('content_json')->nullable();
            $table->string('storage_disk')->nullable();
            $table->string('storage_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['ai_upload_id', 'output_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_outputs');
    }
};
