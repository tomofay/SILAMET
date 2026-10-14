<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login via NIS/NIP token untuk Kiosk & Devices.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'nis_nip' => 'required|string',
            'device_id' => 'nullable|string',
        ]);

        // Find user by nis_nip
        $user = \App\Models\User::where('nis_nip', $validated['nis_nip'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 401);
        }

        // Create Sanctum token
        $token = $user->createToken('kiosk-' . ($validated['device_id'] ?? 'web'))->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nis_nip' => $user->nis_nip,
                    'phone' => $user->phone,
                    'role' => $user->role?->value ?? 'siswa',
                ],
            ],
        ]);
    }

    /**
     * Logout & invalidate Sanctum token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true]);
    }
}
