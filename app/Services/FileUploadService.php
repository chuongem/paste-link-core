<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    public const MAX_UPLOAD_SESSION_BYTES = 300 * 1024 * 1024;

    public function store(User $user, UploadedFile $uploadedFile): File
    {
        $disk = (string) config('filesystems.uploads_disk', 'public_root');
        $storedName = $this->uniqueStoredName($disk, $uploadedFile);
        $path = $uploadedFile->storeAs('', $storedName, $disk);

        return File::query()->create([
            'user_id' => $user->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'size' => $uploadedFile->getSize(),
        ]);
    }

    /**
     * @param  array<int, UploadedFile>  $uploadedFiles
     * @return array<int, File>
     */
    public function storeMany(User $user, array $uploadedFiles): array
    {
        return array_map(
            fn (UploadedFile $uploadedFile): File => $this->store($user, $uploadedFile),
            array_values($uploadedFiles),
        );
    }

    public function url(File $file): ?string
    {
        $configuredUrl = config("filesystems.disks.{$file->disk}.url");

        if (is_string($configuredUrl) && $configuredUrl !== '') {
            return rtrim($configuredUrl, '/').'/'.ltrim($file->path, '/');
        }

        $disk = Storage::disk($file->disk);

        return method_exists($disk, 'url') ? $disk->url($file->path) : null;
    }

    public function fullPath(File $file): ?string
    {
        $url = $this->url($file);

        if ($url === null) {
            return null;
        }

        return Str::startsWith($url, ['http://', 'https://'])
            ? $url
            : url($url);
    }

    private function uniqueStoredName(string $disk, UploadedFile $uploadedFile): string
    {
        $extension = $uploadedFile->extension()
            ?: $uploadedFile->getClientOriginalExtension()
            ?: 'bin';
        $originalBaseName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBaseName = Str::slug($originalBaseName) ?: 'file';
        $baseName = now()->format('Ymd').'-'.$safeBaseName;

        $suffix = null;
        do {
            $storedName = $baseName.($suffix ? "-{$suffix}" : '').'.'.$extension;
            $path = $storedName;
            $suffix = Str::lower(Str::random(6));
        } while (Storage::disk($disk)->exists($path));

        return $storedName;
    }
}
