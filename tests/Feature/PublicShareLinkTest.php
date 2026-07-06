<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\ShareLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicShareLinkTest extends TestCase
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

    public function test_authenticated_user_can_create_share_link_for_owned_file(): void
    {
        $user = User::factory()->create();
        $file = $this->createFileFor($user, 'document.pdf', 'application/pdf');

        $response = $this->withToken($user->createToken('api')->plainTextToken)
            ->postJson("/api/v1/files/{$file->id}/share-links");

        $code = $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Share link created successfully.')
            ->assertJsonPath('data.file_id', $file->id)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.password_required', false)
            ->assertJsonPath('data.view_count', 0)
            ->assertJsonPath('data.download_count', 0)
            ->assertJsonPath('data.file.original_name', 'document.pdf')
            ->json('data.code');

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $code);
        $this->assertSame("https://paste-link-core.onrender.com/api/v1/f/{$code}", $response->json('data.public_url'));
        $this->assertSame("https://paste-link-core.onrender.com/api/v1/f/{$code}/download", $response->json('data.download_url'));
        $this->assertDatabaseHas('share_links', [
            'file_id' => $file->id,
            'user_id' => $user->id,
            'code' => $code,
            'is_active' => true,
        ]);
    }

    public function test_create_share_link_is_idempotent_for_same_file(): void
    {
        $user = User::factory()->create();
        $file = $this->createFileFor($user, 'document.pdf', 'application/pdf');
        $token = $user->createToken('api')->plainTextToken;

        $firstCode = $this->withToken($token)
            ->postJson("/api/v1/files/{$file->id}/share-links")
            ->assertCreated()
            ->json('data.code');

        $this->withToken($token)
            ->postJson("/api/v1/files/{$file->id}/share-links")
            ->assertOk()
            ->assertJsonPath('message', 'Share link retrieved successfully.')
            ->assertJsonPath('data.code', $firstCode);

        $this->assertDatabaseCount('share_links', 1);
    }

    public function test_owner_can_update_password_expiry_and_active_status(): void
    {
        $user = User::factory()->create();
        $file = $this->createFileFor($user, 'document.pdf', 'application/pdf');
        $shareLink = $this->createShareLinkFor($user, $file);
        $expiresAt = now()->addDays(7)->toISOString();

        $this->withToken($user->createToken('api')->plainTextToken)
            ->patchJson("/api/v1/share-links/{$shareLink->id}", [
                'password' => 'secret-password',
                'expires_at' => $expiresAt,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Share link updated successfully.')
            ->assertJsonPath('data.password_required', true)
            ->assertJsonPath('data.is_active', false);

        $shareLink->refresh();
        $this->assertTrue(Hash::check('secret-password', $shareLink->password_hash));
        $this->assertFalse($shareLink->is_active);
        $this->assertNotNull($shareLink->expires_at);
    }

    public function test_owner_can_clear_share_link_password_and_expiry(): void
    {
        $shareLink = $this->createShareLink([
            'password_hash' => Hash::make('secret-password'),
            'expires_at' => now()->addDay(),
        ]);

        $this->withToken($shareLink->user->createToken('api')->plainTextToken)
            ->patchJson("/api/v1/share-links/{$shareLink->id}", [
                'password' => null,
                'expires_at' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.password_required', false)
            ->assertJsonPath('data.expires_at', null);

        $this->assertDatabaseHas('share_links', [
            'id' => $shareLink->id,
            'password_hash' => null,
            'expires_at' => null,
        ]);
    }

    public function test_user_cannot_create_share_link_for_another_users_file(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherFile = $this->createFileFor($otherUser, 'other-user.txt', 'text/plain');

        $this->withToken($user->createToken('api')->plainTextToken)
            ->postJson("/api/v1/files/{$otherFile->id}/share-links")
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'SHARE_LINK_NOT_FOUND');

        $this->assertDatabaseCount('share_links', 0);
    }

    public function test_public_share_link_metadata_can_be_retrieved_by_code(): void
    {
        $shareLink = $this->createShareLink();

        $this->getJson("/api/v1/share-links/{$shareLink->code}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Share link retrieved successfully.')
            ->assertJsonPath('data.code', $shareLink->code)
            ->assertJsonPath('data.file.original_name', 'document.pdf');
    }

    public function test_public_file_page_payload_can_be_retrieved_by_code(): void
    {
        $shareLink = $this->createShareLink();

        $this->getJson("/api/v1/f/{$shareLink->code}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Public file retrieved successfully.')
            ->assertJsonPath('data.share_link.code', $shareLink->code)
            ->assertJsonPath('data.share_link.view_count', 1)
            ->assertJsonPath('data.file.original_name', 'document.pdf');

        $this->assertDatabaseHas('share_links', [
            'id' => $shareLink->id,
            'view_count' => 1,
        ]);
    }

    public function test_public_download_streams_the_shared_file(): void
    {
        $shareLink = $this->createShareLink();

        $this->get("/api/v1/f/{$shareLink->code}/download")
            ->assertOk()
            ->assertDownload('document.pdf');

        $this->assertDatabaseHas('share_links', [
            'id' => $shareLink->id,
            'download_count' => 1,
        ]);
    }

    public function test_inactive_share_link_is_not_publicly_available(): void
    {
        $shareLink = $this->createShareLink(['is_active' => false]);

        $this->getJson("/api/v1/f/{$shareLink->code}")
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'SHARE_LINK_NOT_FOUND');
    }

    public function test_expired_share_link_is_not_publicly_available(): void
    {
        $shareLink = $this->createShareLink(['expires_at' => now()->subMinute()]);

        $this->getJson("/api/v1/f/{$shareLink->code}")
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'SHARE_LINK_NOT_FOUND');
    }

    public function test_password_protected_share_link_requires_password_for_public_file_and_download(): void
    {
        $shareLink = $this->createShareLink([
            'password_hash' => Hash::make('secret-password'),
        ]);

        $this->getJson("/api/v1/f/{$shareLink->code}")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'SHARE_LINK_PASSWORD_REQUIRED');

        $this->getJson("/api/v1/f/{$shareLink->code}?password=wrong-password")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'SHARE_LINK_PASSWORD_INVALID');

        $this->getJson("/api/v1/f/{$shareLink->code}?password=secret-password")
            ->assertOk()
            ->assertJsonPath('data.file.original_name', 'document.pdf');

        $this->get("/api/v1/f/{$shareLink->code}/download?password=secret-password")
            ->assertOk()
            ->assertDownload('document.pdf');
    }

    public function test_public_password_verify_endpoint_validates_password(): void
    {
        $shareLink = $this->createShareLink([
            'password_hash' => Hash::make('secret-password'),
        ]);

        $this->postJson("/api/v1/share-links/{$shareLink->code}/verify-password", [
            'password' => 'wrong-password',
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'SHARE_LINK_PASSWORD_INVALID');

        $this->postJson("/api/v1/share-links/{$shareLink->code}/verify-password", [
            'password' => 'secret-password',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.verified', true)
            ->assertJsonPath('data.password_required', true);
    }

    public function test_owner_can_delete_share_link(): void
    {
        $shareLink = $this->createShareLink();

        $this->withToken($shareLink->user->createToken('api')->plainTextToken)
            ->deleteJson("/api/v1/share-links/{$shareLink->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Share link deleted successfully.');

        $this->assertSoftDeleted('share_links', ['id' => $shareLink->id]);

        $this->getJson("/api/v1/f/{$shareLink->code}")
            ->assertNotFound()
            ->assertJsonPath('code', 'SHARE_LINK_NOT_FOUND');
    }

    public function test_owner_can_regenerate_share_link_code_and_reset_counters(): void
    {
        $shareLink = $this->createShareLink([
            'view_count' => 5,
            'download_count' => 3,
            'last_viewed_at' => now(),
            'last_downloaded_at' => now(),
        ]);
        $oldCode = $shareLink->code;

        $newCode = $this->withToken($shareLink->user->createToken('api')->plainTextToken)
            ->postJson("/api/v1/share-links/{$shareLink->id}/regenerate")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Share link regenerated successfully.')
            ->assertJsonPath('data.view_count', 0)
            ->assertJsonPath('data.download_count', 0)
            ->assertJsonPath('data.last_viewed_at', null)
            ->assertJsonPath('data.last_downloaded_at', null)
            ->json('data.code');

        $this->assertNotSame($oldCode, $newCode);

        $this->getJson("/api/v1/f/{$oldCode}")
            ->assertNotFound()
            ->assertJsonPath('code', 'SHARE_LINK_NOT_FOUND');

        $this->getJson("/api/v1/f/{$newCode}")
            ->assertOk()
            ->assertJsonPath('data.share_link.code', $newCode);
    }

    public function test_qr_code_endpoint_returns_shareable_qr_data(): void
    {
        $shareLink = $this->createShareLink();

        $this->getJson("/api/v1/f/{$shareLink->code}/qr-code")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'QR code data retrieved successfully.')
            ->assertJsonPath('data.code', $shareLink->code)
            ->assertJsonPath('data.qr_data', "https://paste-link-core.onrender.com/api/v1/f/{$shareLink->code}")
            ->assertJsonPath('data.download_url', "https://paste-link-core.onrender.com/api/v1/f/{$shareLink->code}/download");
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createShareLink(array $attributes = []): ShareLink
    {
        $user = User::factory()->create();
        $file = $this->createFileFor($user, 'document.pdf', 'application/pdf');

        return ShareLink::query()->create(array_merge([
            'file_id' => $file->id,
            'user_id' => $user->id,
            'code' => 'A7X29K',
            'is_active' => true,
        ], $attributes))->load('file', 'user');
    }

    private function createShareLinkFor(User $user, File $file): ShareLink
    {
        return ShareLink::query()->create([
            'file_id' => $file->id,
            'user_id' => $user->id,
            'code' => 'B8Y30L',
            'is_active' => true,
        ])->load('file', 'user');
    }

    private function createFileFor(User $user, string $name, string $mimeType): File
    {
        $storedName = now()->format('Ymd').'-'.$name;
        Storage::disk('public_root')->put($storedName, 'test file contents');

        return File::query()->create([
            'user_id' => $user->id,
            'disk' => 'public_root',
            'path' => $storedName,
            'original_name' => $name,
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
            'size' => 18,
        ]);
    }
}
