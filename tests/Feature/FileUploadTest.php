<?php

namespace Tests\Feature;

use App\Models\File as FileModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.url', 'https://paste-link-core.onrender.com');
        Config::set('filesystems.uploads_disk', 'public_root');
        Config::set('filesystems.disks.public_root.url', 'https://paste-link-core.onrender.com');
        Storage::fake('public_root');
    }

    public function test_authenticated_user_can_upload_one_file_with_multi_payload(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;
        $upload = UploadedFile::fake()->create('document.pdf', 128, 'application/pdf');

        $response = $this->withToken($token)->postJson('/api/v1/files', [
            'files' => [$upload],
        ]);

        $path = $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Files uploaded successfully.')
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('meta.source_file_count', 1)
            ->assertJsonPath('data.0.disk', 'public_root')
            ->assertJsonPath('data.0.original_name', 'document.pdf')
            ->assertJsonPath('data.0.mime_type', 'application/pdf')
            ->assertJsonPath('data.0.path', now()->format('Ymd').'-document.pdf')
            ->assertJsonPath('data.0.url', 'https://paste-link-core.onrender.com/'.now()->format('Ymd').'-document.pdf')
            ->assertJsonPath('data.0.full_path', 'https://paste-link-core.onrender.com/'.now()->format('Ymd').'-document.pdf')
            ->assertJsonPath('data.0.download_url', 'https://paste-link-core.onrender.com/'.now()->format('Ymd').'-document.pdf')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'disk', 'path', 'url', 'full_path', 'download_url', 'original_name', 'stored_name', 'mime_type', 'size', 'created_at'],
                ],
            ])
            ->json('data.0.path');

        $this->assertSame(now()->format('Ymd').'-document.pdf', $path);
        Storage::disk('public_root')->assertExists($path);

        $this->assertDatabaseHas('files', [
            'user_id' => $user->id,
            'disk' => 'public_root',
            'path' => $path,
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }

    public function test_authenticated_user_can_upload_multiple_files(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/files', [
            'files' => [
                UploadedFile::fake()->create('a.txt', 10, 'text/plain'),
                UploadedFile::fake()->create('b.jpg', 20, 'image/jpeg'),
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Files uploaded successfully.')
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('meta.source_file_count', 2)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.original_name', now()->format('Ymd').'-files.zip')
            ->assertJsonPath('data.0.mime_type', 'application/zip')
            ->assertJsonPath('data.0.path', now()->format('Ymd').'-files.zip')
            ->assertJsonPath('data.0.download_url', 'https://paste-link-core.onrender.com/'.now()->format('Ymd').'-files.zip');

        $zipPath = $response->json('data.0.path');
        Storage::disk('public_root')->assertExists($zipPath);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('public_root')->path($zipPath)));
        $this->assertNotFalse($zip->locateName('a.txt'));
        $this->assertNotFalse($zip->locateName('b.jpg'));
        $zip->close();

        $this->assertDatabaseCount('files', 1);
        $this->assertDatabaseHas('files', [
            'user_id' => $user->id,
            'disk' => 'public_root',
            'path' => $zipPath,
            'original_name' => now()->format('Ymd').'-files.zip',
            'mime_type' => 'application/zip',
        ]);
    }

    public function test_upload_requires_authentication(): void
    {
        $this->postJson('/api/v1/files', [
            'files' => [UploadedFile::fake()->create('document.pdf', 1, 'application/pdf')],
        ])->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_upload_validates_required_files(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('api')->plainTextToken)
            ->postJson('/api/v1/files', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['files']);
    }

    public function test_multiple_uploads_validate_total_session_size(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/files', [
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

    public function test_authenticated_user_can_list_only_their_files(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->createFileFor($user, 'owned-a.txt', 'text/plain');
        $this->createFileFor($otherUser, 'other-user.txt', 'text/plain');
        $this->travel(1)->seconds();
        $this->createFileFor($user, 'owned-b.jpg', 'image/jpeg');

        $this->withToken($token)->getJson('/api/v1/files')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Files retrieved successfully.')
            ->assertJsonPath('meta.count', 2)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.original_name', 'owned-b.jpg')
            ->assertJsonPath('data.1.original_name', 'owned-a.txt');
    }

    public function test_authenticated_user_can_view_their_file_details(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;
        $file = $this->createFileFor($user, 'document.pdf', 'application/pdf');

        $this->withToken($token)->getJson("/api/v1/files/{$file['id']}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'File retrieved successfully.')
            ->assertJsonPath('data.id', $file['id'])
            ->assertJsonPath('data.original_name', 'document.pdf')
            ->assertJsonPath('data.path', $file['path']);
    }

    public function test_file_detail_returns_not_found_for_another_users_file(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;
        $otherFile = $this->createFileFor($otherUser, 'other-user.txt', 'text/plain');

        $this->withToken($token)->getJson("/api/v1/files/{$otherFile['id']}")
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'FILE_NOT_FOUND');
    }

    public function test_authenticated_user_can_delete_their_file_and_storage_object(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;
        $file = $this->createFileFor($user, 'document.pdf', 'application/pdf');

        Storage::disk('public_root')->assertExists($file['path']);

        $this->withToken($token)->deleteJson("/api/v1/files/{$file['id']}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'File deleted successfully.');

        Storage::disk('public_root')->assertMissing($file['path']);
        $this->assertDatabaseMissing('files', ['id' => $file['id']]);
    }

    public function test_delete_returns_not_found_for_another_users_file(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;
        $otherFile = $this->createFileFor($otherUser, 'other-user.txt', 'text/plain');

        $this->withToken($token)->deleteJson("/api/v1/files/{$otherFile['id']}")
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'FILE_NOT_FOUND');

        Storage::disk('public_root')->assertExists($otherFile['path']);
        $this->assertDatabaseHas('files', ['id' => $otherFile['id']]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createFileFor(User $user, string $name, string $mimeType): array
    {
        $storedName = now()->format('Ymd').'-'.$name;
        Storage::disk('public_root')->put($storedName, 'test file contents');

        return FileModel::query()->create([
            'user_id' => $user->id,
            'disk' => 'public_root',
            'path' => $storedName,
            'original_name' => $name,
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
            'size' => 18,
        ])->toArray();
    }
}
