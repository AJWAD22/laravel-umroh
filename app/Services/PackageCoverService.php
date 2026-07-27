<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PackageCoverService
{
    public function store(UploadedFile $image, ?string $oldPath): string
    {
        $path = $image->store('packages', 'public');

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return $path;
    }
}
