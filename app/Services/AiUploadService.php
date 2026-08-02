<?php

namespace App\Services;

use App\Models\AiUpload;
use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AiUploadService
{
    public function store(User $user, UploadedFile $uploadedFile): AiUpload
    {
        $disk = (string) config('ai.uploads_disk', 'public_root');
        $extension = $this->extensionFromUpload($uploadedFile);
        $storedPath = $this->uniqueStoredPath($disk, $uploadedFile->getClientOriginalName(), $extension);
        $path = $uploadedFile->storeAs(dirname($storedPath), basename($storedPath), $disk);

        if ($path === false) {
            throw new RuntimeException('Unable to store AI upload.');
        }

        return AiUpload::query()->create([
            'user_id' => $user->id,
            'source_type' => 'direct_upload',
            'original_name' => $uploadedFile->getClientOriginalName(),
            'display_name' => $uploadedFile->getClientOriginalName(),
            'mime_type' => $uploadedFile->getClientMimeType(),
            'extension' => $extension,
            'size_bytes' => $uploadedFile->getSize() ?: 0,
            'storage_disk' => $disk,
            'storage_path' => $path,
            'checksum' => $this->checksum($uploadedFile->getRealPath() ?: null),
            'file_kind' => $this->detectFileKind($extension, $uploadedFile->getClientMimeType()),
            'status' => 'uploaded',
            'metadata' => $this->metadataFor($extension, $uploadedFile->getSize() ?: 0),
        ]);
    }

    public function importFile(User $user, File $file): AiUpload
    {
        $disk = (string) config('ai.uploads_disk', $file->disk);
        $extension = Str::lower(pathinfo($file->original_name, PATHINFO_EXTENSION));
        $storedPath = $this->uniqueStoredPath($disk, $file->original_name, $extension);
        $sourceDisk = Storage::disk($file->disk);
        $targetDisk = Storage::disk($disk);
        $stream = $sourceDisk->readStream($file->path);

        if ($stream === false) {
            throw new RuntimeException('Unable to read source file.');
        }

        $targetDisk->put($storedPath, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return AiUpload::query()->create([
            'user_id' => $user->id,
            'source_file_id' => $file->id,
            'source_type' => 'imported_file',
            'original_name' => $file->original_name,
            'display_name' => $file->original_name,
            'mime_type' => $file->mime_type,
            'extension' => $extension ?: null,
            'size_bytes' => $file->size,
            'storage_disk' => $disk,
            'storage_path' => $storedPath,
            'checksum' => $this->storageChecksum($disk, $storedPath),
            'file_kind' => $this->detectFileKind($extension, $file->mime_type),
            'status' => 'uploaded',
            'metadata' => $this->metadataFor($extension, $file->size),
        ]);
    }

    public function deletePhysicalFile(AiUpload $upload): void
    {
        Storage::disk($upload->storage_disk)->delete($upload->storage_path);
    }

    public function detectFileKind(?string $extension, ?string $mimeType): string
    {
        $extension = Str::lower((string) $extension);
        $supported = (array) config('ai.supported_extensions', []);

        foreach ($supported as $kind => $extensions) {
            if (in_array($extension, $extensions, true)) {
                return (string) $kind;
            }
        }

        if (is_string($mimeType)) {
            return match (true) {
                Str::startsWith($mimeType, 'text/') => 'text',
                Str::startsWith($mimeType, 'audio/') => 'audio',
                Str::startsWith($mimeType, 'video/') => 'video',
                in_array($mimeType, ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true) => 'document',
                default => 'unknown',
            };
        }

        return 'unknown';
    }

    /**
     * @return array<string, mixed>
     */
    private function metadataFor(?string $extension, int $sizeBytes): array
    {
        return [
            'extension' => $extension,
            'size_bytes' => $sizeBytes,
        ];
    }

    private function uniqueStoredPath(string $disk, string $originalName, ?string $extension): string
    {
        $extension = $extension ?: pathinfo($originalName, PATHINFO_EXTENSION) ?: 'bin';
        $baseName = pathinfo($originalName, PATHINFO_FILENAME) ?: 'file';
        $safeBaseName = Str::slug($baseName) ?: 'file';
        $prefix = 'ai-uploads/'.now()->format('Ymd');
        $suffix = null;

        do {
            $fileName = $safeBaseName.($suffix ? "-{$suffix}" : '').'.'.$extension;
            $path = "{$prefix}/{$fileName}";
            $suffix = Str::lower(Str::random(6));
        } while (Storage::disk($disk)->exists($path));

        return $path;
    }

    private function extensionFromUpload(UploadedFile $uploadedFile): ?string
    {
        $extension = $uploadedFile->extension()
            ?: $uploadedFile->getClientOriginalExtension()
            ?: null;

        return $extension ? Str::lower($extension) : null;
    }

    private function checksum(?string $path): ?string
    {
        return $path && is_file($path) ? hash_file('sha256', $path) : null;
    }

    private function storageChecksum(string $disk, string $path): ?string
    {
        $localPath = Storage::disk($disk)->path($path);

        return is_file($localPath) ? hash_file('sha256', $localPath) : null;
    }
}
