<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_upload_id')->constrained('ai_uploads')->cascadeOnDelete();
            $table->string('job_type', 32);
            $table->string('provider', 64);
            $table->string('model', 128)->nullable();
            $table->string('status', 32)->default('pending');
            $table->json('input_options')->nullable();
            $table->longText('output_text')->nullable();
            $table->json('output_json')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('credit_cost')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['ai_upload_id', 'job_type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_jobs');
    }
};
