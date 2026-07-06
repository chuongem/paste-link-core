<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\ShareLink;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShareLinkController extends Controller
{
    public function store(Request $request, int $id): JsonResponse
    {
        $validated = $this->validateShareLinkOptions($request);
        $file = File::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($id)
            ->first();

        if (! $file instanceof File) {
            return $this->shareLinkNotFoundResponse();
        }

        $shareLink = ShareLink::query()
            ->withTrashed()
            ->where('file_id', $file->id)
            ->where('user_id', $request->user()->id)
            ->first();
        $wasCreated = false;

        if (! $shareLink instanceof ShareLink) {
            $shareLink = ShareLink::query()->create([
                'file_id' => $file->id,
                'user_id' => $request->user()->id,
                'code' => $this->uniqueCode(),
                'is_active' => true,
            ]);
            $wasCreated = true;
        } elseif ($shareLink->trashed()) {
            $shareLink->restore();
            $wasCreated = true;
        }

        $this->applyShareLinkOptions($shareLink, $validated);

        if (! $shareLink->is_active) {
            $shareLink->forceFill(['is_active' => true])->save();
        }

        return ApiResponse::success(
            data: $this->shareLinkPayload($shareLink->fresh('file')),
            message: $wasCreated ? 'Share link created successfully.' : 'Share link retrieved successfully.',
            status: $wasCreated ? 201 : 200,
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $shareLink = $this->findOwnedShareLink($request, $id);

        if (! $shareLink instanceof ShareLink) {
            return $this->shareLinkNotFoundResponse();
        }

        $validated = $this->validateShareLinkOptions($request, requireOneField: true);
        $this->applyShareLinkOptions($shareLink, $validated);

        return ApiResponse::success(
            data: $this->shareLinkPayload($shareLink->fresh('file')),
            message: 'Share link updated successfully.',
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $shareLink = $this->findOwnedShareLink($request, $id);

        if (! $shareLink instanceof ShareLink) {
            return $this->shareLinkNotFoundResponse();
        }

        $shareLink->delete();

        return ApiResponse::success(message: 'Share link deleted successfully.');
    }

    public function regenerate(Request $request, int $id): JsonResponse
    {
        $shareLink = $this->findOwnedShareLink($request, $id);

        if (! $shareLink instanceof ShareLink) {
            return $this->shareLinkNotFoundResponse();
        }

        $shareLink->forceFill([
            'code' => $this->uniqueCode(),
            'is_active' => true,
            'view_count' => 0,
            'download_count' => 0,
            'last_viewed_at' => null,
            'last_downloaded_at' => null,
        ])->save();

        return ApiResponse::success(
            data: $this->shareLinkPayload($shareLink->fresh('file')),
            message: 'Share link regenerated successfully.',
        );
    }

    public function show(string $code): JsonResponse
    {
        $shareLink = $this->findActiveShareLink($code);

        if (! $shareLink instanceof ShareLink) {
            return $this->shareLinkNotFoundResponse();
        }

        return ApiResponse::success(
            data: $this->shareLinkPayload($shareLink),
            message: 'Share link retrieved successfully.',
        );
    }

    public function verifyPassword(Request $request, string $code): JsonResponse
    {
        $shareLink = $this->findActiveShareLink($code);

        if (! $shareLink instanceof ShareLink) {
            return $this->shareLinkNotFoundResponse();
        }

        if (! $this->hasPassword($shareLink)) {
            return ApiResponse::success(
                data: ['verified' => true, 'password_required' => false],
                message: 'Share link does not require a password.',
            );
        }

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($validated['password'], (string) $shareLink->password_hash)) {
            return $this->passwordInvalidResponse();
        }

        return ApiResponse::success(
            data: ['verified' => true, 'password_required' => true],
            message: 'Share link password verified successfully.',
        );
    }

    public function publicFile(Request $request, string $code): JsonResponse
    {
        $shareLink = $this->findActiveShareLink($code);

        if (! $shareLink instanceof ShareLink) {
            return $this->shareLinkNotFoundResponse();
        }

        $passwordResponse = $this->passwordResponseIfBlocked($request, $shareLink);

        if ($passwordResponse instanceof JsonResponse) {
            return $passwordResponse;
        }

        $shareLink->increment('view_count');
        $shareLink->forceFill(['last_viewed_at' => now()])->save();
        $shareLink->refresh();

        return ApiResponse::success(
            data: [
                'share_link' => $this->shareLinkPayload($shareLink),
                'file' => $this->publicFilePayload($shareLink),
            ],
            message: 'Public file retrieved successfully.',
        );
    }

    public function download(Request $request, string $code): JsonResponse|StreamedResponse
    {
        $shareLink = $this->findActiveShareLink($code);

        if (! $shareLink instanceof ShareLink) {
            return $this->shareLinkNotFoundResponse();
        }

        $passwordResponse = $this->passwordResponseIfBlocked($request, $shareLink);

        if ($passwordResponse instanceof JsonResponse) {
            return $passwordResponse;
        }

        $file = $shareLink->file;

        if (! Storage::disk($file->disk)->exists($file->path)) {
            return ApiResponse::error(
                message: 'File storage object not found.',
                status: 404,
                code: 'FILE_STORAGE_NOT_FOUND',
            );
        }

        $shareLink->increment('download_count');
        $shareLink->forceFill(['last_downloaded_at' => now()])->save();

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function qrCode(string $code): JsonResponse
    {
        $shareLink = $this->findActiveShareLink($code);

        if (! $shareLink instanceof ShareLink) {
            return $this->shareLinkNotFoundResponse();
        }

        return ApiResponse::success(
            data: [
                'code' => $shareLink->code,
                'qr_data' => $this->publicUrl($shareLink),
                'public_url' => $this->publicUrl($shareLink),
                'download_url' => $this->downloadUrl($shareLink),
            ],
            message: 'QR code data retrieved successfully.',
        );
    }

    private function findActiveShareLink(string $code): ?ShareLink
    {
        return ShareLink::query()
            ->with('file')
            ->where('code', Str::upper($code))
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    private function findOwnedShareLink(Request $request, int $id): ?ShareLink
    {
        return ShareLink::query()
            ->with('file')
            ->where('user_id', $request->user()->id)
            ->whereKey($id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function shareLinkPayload(ShareLink $shareLink): array
    {
        return [
            'id' => $shareLink->id,
            'file_id' => $shareLink->file_id,
            'code' => $shareLink->code,
            'is_active' => $shareLink->is_active,
            'password_required' => $this->hasPassword($shareLink),
            'expires_at' => $shareLink->expires_at,
            'view_count' => $shareLink->view_count,
            'download_count' => $shareLink->download_count,
            'last_viewed_at' => $shareLink->last_viewed_at,
            'last_downloaded_at' => $shareLink->last_downloaded_at,
            'public_url' => $this->publicUrl($shareLink),
            'download_url' => $this->downloadUrl($shareLink),
            'qr_code_url' => $this->qrCodeUrl($shareLink),
            'created_at' => $shareLink->created_at,
            'file' => $this->publicFilePayload($shareLink),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateShareLinkOptions(Request $request, bool $requireOneField = false): array
    {
        if ($requireOneField && ! $request->hasAny(['password', 'expires_at', 'is_active'])) {
            throw ValidationException::withMessages([
                'share_link' => ['At least one share link option is required.'],
            ]);
        }

        $rules = [
            'password' => ['sometimes', 'nullable', 'string', 'min:4', 'max:255'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applyShareLinkOptions(ShareLink $shareLink, array $validated): void
    {
        $updates = [];

        if (array_key_exists('password', $validated)) {
            $updates['password_hash'] = $validated['password'] === null
                ? null
                : Hash::make((string) $validated['password']);
        }

        if (array_key_exists('expires_at', $validated)) {
            $updates['expires_at'] = $validated['expires_at'];
        }

        if (array_key_exists('is_active', $validated)) {
            $updates['is_active'] = (bool) $validated['is_active'];
        }

        if ($updates !== []) {
            $shareLink->forceFill($updates)->save();
        }
    }

    private function passwordResponseIfBlocked(Request $request, ShareLink $shareLink): ?JsonResponse
    {
        if (! $this->hasPassword($shareLink)) {
            return null;
        }

        $password = $request->query('password');

        if (! is_string($password) || $password === '') {
            return ApiResponse::error(
                message: 'Share link password is required.',
                status: 403,
                code: 'SHARE_LINK_PASSWORD_REQUIRED',
            );
        }

        if (! Hash::check($password, (string) $shareLink->password_hash)) {
            return $this->passwordInvalidResponse();
        }

        return null;
    }

    private function hasPassword(ShareLink $shareLink): bool
    {
        return is_string($shareLink->password_hash) && $shareLink->password_hash !== '';
    }

    /**
     * @return array<string, mixed>
     */
    private function publicFilePayload(ShareLink $shareLink): array
    {
        $file = $shareLink->file;

        return [
            'id' => $file->id,
            'original_name' => $file->original_name,
            'stored_name' => $file->stored_name,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'created_at' => $file->created_at,
        ];
    }

    private function uniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (ShareLink::query()->where('code', $code)->exists());

        return $code;
    }

    private function publicUrl(ShareLink $shareLink): string
    {
        return $this->appUrl("/api/v1/f/{$shareLink->code}");
    }

    private function downloadUrl(ShareLink $shareLink): string
    {
        return $this->appUrl("/api/v1/f/{$shareLink->code}/download");
    }

    private function qrCodeUrl(ShareLink $shareLink): string
    {
        return $this->appUrl("/api/v1/f/{$shareLink->code}/qr-code");
    }

    private function appUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }

    private function shareLinkNotFoundResponse(): JsonResponse
    {
        return ApiResponse::error(
            message: 'Share link not found.',
            status: 404,
            code: 'SHARE_LINK_NOT_FOUND',
        );
    }

    private function passwordInvalidResponse(): JsonResponse
    {
        return ApiResponse::error(
            message: 'Share link password is invalid.',
            status: 403,
            code: 'SHARE_LINK_PASSWORD_INVALID',
        );
    }
}
