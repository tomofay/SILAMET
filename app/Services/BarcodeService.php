<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BarcodeService
{
    /**
     * Generate QR code PNG untuk item/barcode.
     */
    public function generate(string $data, string $filename): string
    {
        $path = 'barcodes/' . $filename . '.png';
        
        QrCode::format('png')
            ->size(300)
            ->generate($data, storage_path('app/public/' . $path));
        
        return $path;
    }

    /**
     * Hapus file barcode.
     */
    public function delete(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }
}
