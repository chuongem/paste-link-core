<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

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
        $uploadedFiles = array_values($uploadedFiles);

        if (count($uploadedFiles) === 1) {
            return [$this->store($user, $uploadedFiles[0])];
        }

        return [$this->storeZip($user, $uploadedFiles)];
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

    public function deletePhysicalFile(File $file): void
    {
        Storage::disk($file->disk)->delete($file->path);
    }

    /**
     * @param  array<int, UploadedFile>  $uploadedFiles
     */
    private function storeZip(User $user, array $uploadedFiles): File
    {
        $disk = (string) config('filesystems.uploads_disk', 'public_root');
        $storedName = $this->uniqueStoredNameForBase($disk, 'files', 'zip');
        $tempPath = tempnam(sys_get_temp_dir(), 'pastelink-zip-');

        if ($tempPath === false) {
            throw new RuntimeException('Unable to create temporary zip file.');
        }

        $zip = new ZipArchive;

        if ($zip->open($tempPath, ZipArchive::OVERWRITE) !== true) {
            @unlink($tempPath);

            throw new RuntimeException('Unable to open temporary zip file.');
        }

        $entryNames = [];

        foreach ($uploadedFiles as $uploadedFile) {
            $entryName = $this->uniqueZipEntryName($uploadedFile, $entryNames);
            $realPath = $uploadedFile->getRealPath();

            if ($realPath === false || ! $zip->addFile($realPath, $entryName)) {
                $zip->close();
                @unlink($tempPath);

                throw new RuntimeException('Unable to add uploaded file to zip archive.');
            }

            $entryNames[] = $entryName;
        }

        if (! $zip->close()) {
            @unlink($tempPath);

            throw new RuntimeException('Unable to finalize zip archive.');
        }

        $stream = fopen($tempPath, 'rb');

        if ($stream === false) {
            @unlink($tempPath);

            throw new RuntimeException('Unable to read temporary zip file.');
        }

        Storage::disk($disk)->put($storedName, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        $size = filesize($tempPath) ?: 0;
        @unlink($tempPath);

        return File::query()->create([
            'user_id' => $user->id,
            'disk' => $disk,
            'path' => $storedName,
            'original_name' => $storedName,
            'stored_name' => $storedName,
            'mime_type' => 'application/zip',
            'size' => $size,
        ]);
    }

    private function uniqueStoredName(string $disk, UploadedFile $uploadedFile): string
    {
        $extension = $uploadedFile->extension()
            ?: $uploadedFile->getClientOriginalExtension()
            ?: 'bin';
        $originalBaseName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBaseName = Str::slug($originalBaseName) ?: 'file';

        return $this->uniqueStoredNameForBase($disk, $safeBaseName, $extension);
    }

    private function uniqueStoredNameForBase(string $disk, string $baseName, string $extension): string
    {
        $baseName = now()->format('Ymd').'-'.(Str::slug($baseName) ?: 'file');
        $suffix = null;

        do {
            $storedName = $baseName.($suffix ? "-{$suffix}" : '').'.'.$extension;
            $path = $storedName;
            $suffix = Str::lower(Str::random(6));
        } while (Storage::disk($disk)->exists($path));

        return $storedName;
    }

    /**
     * @param  array<int, string>  $existingNames
     */
    private function uniqueZipEntryName(UploadedFile $uploadedFile, array $existingNames): string
    {
        $originalName = $uploadedFile->getClientOriginalName() ?: 'file';
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME) ?: 'file';
        $safeBaseName = Str::slug($baseName) ?: 'file';
        $candidate = $safeBaseName.($extension !== '' ? ".{$extension}" : '');
        $suffix = 2;

        while (in_array($candidate, $existingNames, true)) {
            $candidate = $safeBaseName.'-'.$suffix.($extension !== '' ? ".{$extension}" : '');
            $suffix++;
        }

        return $candidate;
    }
}
