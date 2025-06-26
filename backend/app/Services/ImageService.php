<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * Upload an image and return its storage path.
     *
     * @param UploadedFile $file
     * @return string Path stored relative to storage disk
     */
    public function upload(UploadedFile $file): string
    {
        return $file->store('blog_images', 'public');
    }

    /**
     * Delete an image from storage.
     *
     * @param string|null $path
     * @return bool
     */
    public function delete(?string $path): bool
    {
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        return false;
    }
}
