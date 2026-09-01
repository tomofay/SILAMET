<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GateLog;
use App\Services\CameraService;
use App\Services\GateValidationService;
use Illuminate\Http\Request;

class GateApiController extends Controller
{
    /**
     * Endpoint yang dipanggil HW-VX6330 reader setiap kali RFID tag melintasi pintu.
     * Request dari reader berisi EPC tag string.
     */
    public function checkTag(Request $request, GateValidationService $validationService, CameraService $cameraService)
    {
        $validated = $request->validate([
            'epc_tag' => 'required|string',
        ]);

        // 1. Validasi tag menggunakan service layer
        $result = $validationService->checkTag($validated['epc_tag']);

        // 2. Simpan log ke database
        GateLog::create([
            'epc_tag_scanned' => $validated['epc_tag'],
            'action_status' => $result['action'],
            'capture_image_path' => null, // optional - bisa diisi dari parameter request jika ada foto
        ]);

        // 3. Jika denied → trigger sirene + ambil foto pelanggar
        if ($result['action'] === 'denied') {
            // TODO: Trigger relay sirene 12V via GPIO/module relay
            // Ini perlu implementasi service tambahan untuk hardware interface

            // Ambil foto pelanggar dari webcam gate jika ada
            if ($request->has('photo')) {
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->input('photo')));
                $path = $cameraService->capture($imageData, 'photos/gate');
                
                GateLog::where('epc_tag_scanned', $validated['epc_tag'])
                    ->whereNull('capture_image_path')
                    ->latest()
                    ->first()
                    ?->update(['capture_image_path' => $path]);
            }

            return response()->json([
                'success' => true,
                'status' => 'denied',
                'message' => $result['reason'],
                'trigger_sirene' => true,
                'trigger_capture' => true,
            ], 200);
        }

        // 4. Jika allowed — hanya log + alert success
        return response()->json([
            'success' => true,
            'status' => 'allowed',
            'message' => $result['message'],
            'item_name' => $result['item_name'] ?? null,
        ], 200);
    }
}
