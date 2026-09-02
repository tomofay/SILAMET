<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GateLog;
use App\Services\CameraService;
use App\Services\GateValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'photo' => 'nullable|string',
        ]);

        $result = $validationService->checkTag($validated['epc_tag']);
        $isDenied = $result['status'] === 'denied';

        $photoPath = null;
        if ($isDenied && !empty($validated['photo'])) {
            $imageData = base64_decode(
                preg_replace('#^data:image/\w+;base64,#i', '', $validated['photo']),
                true
            );
            if ($imageData !== false) {
                $photoPath = $cameraService->capture($imageData, 'photos/gate');
            }
        }

        DB::transaction(function () use ($validated, $result, $photoPath) {
            GateLog::create([
                'epc_tag_scanned' => $validated['epc_tag'],
                'action_status' => $result['status'],
                'capture_image_path' => $photoPath,
            ]);
        });

        if ($isDenied) {
            return response()->json([
                'success' => true,
                'status' => 'denied',
                'message' => $result['reason'],
                'trigger_sirene' => true,
                'trigger_capture' => true,
            ], 200);
        }

        return response()->json([
            'success' => true,
            'status' => 'allowed',
            'message' => $result['message'],
            'item_name' => $result['item_name'] ?? null,
        ], 200);
    }
}
