<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class CameraService
{
    /**
     * Simpan snapshot webcam dengan ekstensi yang sesuai format biner gambar (JPEG/PNG/GIF/WEBP).
     */
    public function capture(string $imageData, string $type = 'photo'): string
    {
        $extension = $this->detectExtension($imageData);

        // $imageData bisa base64 atau binary dari client/webcam
        $filename = sprintf(
            '%s/%s_%s.%s',
            $type,
            now()->format('Ymd_His'),
            uniqid(),
            $extension
        );

        $path = 'uploads/' . $filename;
        
        Storage::disk('public')->put($path, $imageData);
        
        return $path;
    }

    /**
     * Deteksi ekstensi file gambar dari magic bytes biner.
     */
    private function detectExtension(string $binary): string
    {
        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'jpg';
        }

        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }

        if (str_starts_with($binary, 'GIF87a') || str_starts_with($binary, 'GIF89a')) {
            return 'gif';
        }

        if (strlen($binary) >= 12 && substr($binary, 0, 4) === 'RIFF' && substr($binary, 8, 4) === 'WEBP') {
            return 'webp';
        }

        return 'png';
    }

    /**
     * Hapus foto snapshot.
     */
    public function delete(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }
}
