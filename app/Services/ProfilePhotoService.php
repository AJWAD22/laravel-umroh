<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfilePhotoService
{
    public function store(UploadedFile $photo, ?string $oldPath, string $directory): string
    {
        $path = $photo->store("profiles/{$directory}", 'public');

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return $path;
    }
}
