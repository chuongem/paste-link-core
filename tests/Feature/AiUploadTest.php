<?php

namespace Tests\Feature;

use App\Models\File as FileModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('ai.uploads_disk', 'public_root');
        Config::set('filesystems.uploads_disk', 'public_root');
        Storage::fake('public_root');
    }

    public function test_authenticated_user_can_upload_file_to_ai_workspace(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;
        $upload = UploadedFile::fake()->create('meeting-notes.txt', 12, 'text/plain');

        $response = $this->withToken($token)->postJson('/api/v1/ai/uploads', [
            'file' => $upload,
        ]);

        $path = $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'AI upload created successfully.')
            ->assertJsonPath('data.source_type', 'direct_upload')
            ->assertJsonPath('data.original_name', 'meeting-notes.txt')
            ->assertJsonPath('data.file_kind', 'text')
            ->assertJsonPath('data.status', 'uploaded')
            ->json('data.storage_path');

        Storage::disk('public_root')->assertExists($path);
        $this->assertDatabaseHas('ai_uploads', [
            'user_id' => $user->id,
            'source_type' => 'direct_upload',
            'original_name' => 'meeting-notes.txt',
            'file_kind' => 'text',
            'status' => 'uploaded',
        ]);
    }

    public function test_authenticated_user_can_analyze_ai_upload_and_receive_output(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $uploadId = $this->withToken($token)->postJson('/api/v1/ai/uploads', [
            'file' => UploadedFile::fake()->create('recording.mp3', 20, 'audio/mpeg'),
        ])->assertCreated()->json('data.id');

        $jobId = $this->withToken($token)->postJson("/api/v1/ai/uploads/{$uploadId}/analyze")
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'AI job completed successfully.')
            ->assertJsonPath('data.job_type', 'analyze')
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.credit_cost', 300)
            ->json('data.id');

        $this->assertDatabaseHas('ai_jobs', [
            'id' => $jobId,
            'ai_upload_id' => $uploadId,
            'job_type' => 'analyze',
            'status' => 'completed',
        ]);

        $this->withToken($token)->getJson("/api/v1/ai/uploads/{$uploadId}/outputs")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.output_type', 'analysis')
            ->assertJsonPath('data.0.content_json.status', 'provider_not_configured');

        $this->withToken($token)->getJson("/api/v1/ai/jobs/{$jobId}/result")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonCount(1, 'data.outputs');
    }

    public function test_authenticated_user_can_send_getlink_file_to_ai_workspace(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;
        Storage::disk('public_root')->put('20260709-document.pdf', 'PDF contents');

        $file = FileModel::query()->create([
            'user_id' => $user->id,
            'disk' => 'public_root',
            'path' => '20260709-document.pdf',
            'original_name' => 'document.pdf',
            'stored_name' => '20260709-document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 12,
        ]);

        $path = $this->withToken($token)->postJson("/api/v1/files/{$file->id}/send-to-ai")
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'File sent to AI workspace successfully.')
            ->assertJsonPath('data.source_file_id', $file->id)
            ->assertJsonPath('data.source_type', 'imported_file')
            ->assertJsonPath('data.file_kind', 'document')
            ->json('data.storage_path');

        Storage::disk('public_root')->assertExists($path);
        $this->assertDatabaseHas('ai_uploads', [
            'user_id' => $user->id,
            'source_file_id' => $file->id,
            'source_type' => 'imported_file',
            'file_kind' => 'document',
        ]);
    }

    public function test_ai_upload_requires_authentication(): void
    {
        $this->postJson('/api/v1/ai/uploads', [
            'file' => UploadedFile::fake()->create('meeting-notes.txt', 1, 'text/plain'),
        ])->assertUnauthorized()
            ->assertJsonPath('success', false);
    }
}
