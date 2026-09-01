<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class CameraService
{
    /**
     * Simpan snapshot webcam sebagai file PNG.
     */
    public function capture(array $imageData, string $type = 'photo'): string
    {
        // $imageData bisa base64 atau binary dari client/webcam
        $filename = sprintf(
            '%s/%s_%s.png',
            $type,
            now()->format('Ymd_His'),
            uniqid()
        );

        $path = 'uploads/' . $filename;
        
        Storage::disk('public')->put($path, $imageData);
        
        return $path;
    }

    /**
     * Hapus foto snapshot.
     */
    public function delete(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }
}
