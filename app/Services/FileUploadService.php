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
        $disk = (string) config('filesystems.uploads_disk', 'public');
        $directory = 'uploads/'.now()->format('Y/m/d');
        $storedName = $this->uniqueStoredName($disk, $directory, $uploadedFile);
        $path = $uploadedFile->storeAs($directory, $storedName, $disk);

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

    private function uniqueStoredName(string $disk, string $directory, UploadedFile $uploadedFile): string
    {
        $extension = $uploadedFile->extension()
            ?: $uploadedFile->getClientOriginalExtension()
            ?: 'bin';

        do {
            $storedName = Str::random(12).'.'.$extension;
            $path = "{$directory}/{$storedName}";
        } while (Storage::disk($disk)->exists($path));

        return $storedName;
    }
}
