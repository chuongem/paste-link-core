<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('filesystems.default', 'public');
        Storage::fake('public');
    }

    public function test_authenticated_user_can_upload_single_file(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;
        $upload = UploadedFile::fake()->create('document.pdf', 128, 'application/pdf');

        $response = $this->withToken($token)->postJson('/api/v1/files', [
            'file' => $upload,
        ]);

        $path = $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'File uploaded successfully.')
            ->assertJsonPath('data.disk', 'public')
            ->assertJsonPath('data.original_name', 'document.pdf')
            ->assertJsonPath('data.mime_type', 'application/pdf')
            ->assertJsonStructure([
                'data' => ['id', 'disk', 'path', 'url', 'original_name', 'stored_name', 'mime_type', 'size', 'created_at'],
            ])
            ->json('data.path');

        $this->assertStringStartsWith('uploads/'.now()->format('Y/m/d/'), $path);
        Storage::disk('public')->assertExists($path);

        $this->assertDatabaseHas('files', [
            'user_id' => $user->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }

    public function test_authenticated_user_can_upload_multiple_files(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/files/batch', [
            'files' => [
                UploadedFile::fake()->create('a.txt', 10, 'text/plain'),
                UploadedFile::fake()->create('b.jpg', 20, 'image/jpeg'),
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Files uploaded successfully.')
            ->assertJsonPath('meta.count', 2)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.original_name', 'a.txt')
            ->assertJsonPath('data.1.original_name', 'b.jpg');

        foreach ($response->json('data') as $file) {
            Storage::disk('public')->assertExists($file['path']);
        }

        $this->assertDatabaseCount('files', 2);
    }

    public function test_upload_requires_authentication(): void
    {
        $this->postJson('/api/v1/files', [
            'file' => UploadedFile::fake()->create('document.pdf', 1, 'application/pdf'),
        ])->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_single_upload_validates_required_file(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('api')->plainTextToken)
            ->postJson('/api/v1/files', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['file']);
    }

    public function test_multiple_uploads_validate_total_session_size(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/files/batch', [
            'files' => [
                UploadedFile::fake()->create('large-a.bin', 200 * 1024),
                UploadedFile::fake()->create('large-b.bin', 101 * 1024),
            ],
        ])->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['files']);

        $this->assertDatabaseCount('files', 0);
    }
}
