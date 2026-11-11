<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;

class BarcodeTokenService
{
    /**
     * Karakter aman dan mudah dibaca untuk barcode 1D (tanpa karakter ambigu seperti 0/O, 1/I).
     */
    protected const CHARACTERS = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    // =========================================================================
    // USER TOKEN METHODS (Format: SLM-XXXXXXXX, 12 karakter)
    // =========================================================================

    /**
     * Generate barcode token acak berformat SLM-XXXXXXXX (tepat 12 karakter).
     */
    public static function generateToken(): string
    {
        $maxIndex = strlen(self::CHARACTERS) - 1;
        
        do {
            $randomString = '';
            for ($i = 0; $i < 8; $i++) {
                $randomString .= self::CHARACTERS[random_int(0, $maxIndex)];
            }
            $token = 'SLM-' . $randomString;
        } while (User::where('barcode_token', $token)->exists());

        return $token;
    }

    /**
     * Dapatkan barcode token untuk user. Jika belum ada, otomatis generate dan simpan.
     */
    public static function getOrCreateToken(User $user): string
    {
        if (!empty($user->barcode_token)) {
            return $user->barcode_token;
        }

        $token = self::generateToken();
        $user->barcode_token = $token;
        $user->saveQuietly();

        return $token;
    }

    /**
     * Decode / cari user berdasarkan input scanner (bisa barcode_token SLM-XXXXXXXX atau NIS/NIP asli).
     */
    public static function resolveUser(string $input): ?User
    {
        $clean = trim($input);
        if (empty($clean)) {
            return null;
        }

        // 1. Cek berdasarkan barcode token
        $user = User::where('barcode_token', $clean)->first();
        if ($user) {
            return $user;
        }

        // 2. Cek berdasarkan NIS/NIP asli (backward compatibility)
        return User::where('nis_nip', $clean)->first();
    }

    /**
     * Isi barcode_token untuk seluruh user yang belum memilikinya di database.
     */
    public static function populateMissingTokens(): int
    {
        $users = User::whereNull('barcode_token')->orWhere('barcode_token', '')->get();
        $count = 0;

        foreach ($users as $user) {
            self::getOrCreateToken($user);
            $count++;
        }

        return $count;
    }

    // =========================================================================
    // INVENTORY / ITEM TOKEN METHODS (Format: ITM-XXXXXXXX, 12 karakter)
    // =========================================================================

    /**
     * Generate barcode token acak berformat ITM-XXXXXXXX (tepat 12 karakter).
     * Panjang ideal Code128: strip garis barcode seragam, tebal, renggang, dan optimal dicetak pada kertas stiker label.
     */
    public static function generateItemToken(): string
    {
        $maxIndex = strlen(self::CHARACTERS) - 1;

        do {
            $randomString = '';
            for ($i = 0; $i < 8; $i++) {
                $randomString .= self::CHARACTERS[random_int(0, $maxIndex)];
            }
            $token = 'ITM-' . $randomString;
        } while (Item::where('barcode_token', $token)->exists());

        return $token;
    }

    /**
     * Dapatkan barcode token untuk item. Jika belum ada, otomatis generate dan simpan.
     */
    public static function getOrCreateItemToken(Item $item): string
    {
        if (!empty($item->barcode_token)) {
            return $item->barcode_token;
        }

        $token = self::generateItemToken();
        $item->barcode_token = $token;
        $item->saveQuietly();

        return $token;
    }

    /**
     * Decode / cari item berdasarkan input scanner USB.
     * Mendukung: barcode_token (ITM-XXXXXXXX), item_code asli (AST-RTR-001), id numerik, atau epc_tag.
     */
    public static function resolveItem(string $input): ?Item
    {
        $clean = trim($input);
        if (empty($clean)) {
            return null;
        }

        // 1. Cek via barcode_token
        $item = Item::where('barcode_token', $clean)->first();
        if ($item) {
            return $item;
        }

        // 2. Cek via item_code
        $item = Item::where('item_code', $clean)->first();
        if ($item) {
            return $item;
        }

        // 3. Cek via ID jika numerik
        if (is_numeric($clean)) {
            $item = Item::find($clean);
            if ($item) {
                return $item;
            }
        }

        // 4. Cek via epc_tag (jika reader UHF RFID)
        return Item::where('epc_tag', $clean)->first();
    }

    /**
     * Isi barcode_token untuk seluruh item yang belum memilikinya di database.
     */
    public static function populateMissingItemTokens(): int
    {
        $items = Item::whereNull('barcode_token')->orWhere('barcode_token', '')->get();
        $count = 0;

        foreach ($items as $item) {
            self::getOrCreateItemToken($item);
            $count++;
        }

        return $count;
    }
}
