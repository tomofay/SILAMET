<?php

namespace App\Http\Controllers\Api;

use App\Enums\GateAction;
use App\Http\Controllers\Controller;
use App\Jobs\SendGateViolationTelegramJob;
use App\Models\GateLog;
use App\Services\CameraService;
use App\Services\GateValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GateApiController extends Controller
{
    /**
     * Endpoint yang dipanggil HW-VX6330 reader setiap kali 1 atau banyak RFID tag melintasi pintu.
     * Mendukung single `epc_tag` maupun batch `epc_tags` agar burst multi-tag diproses dalam 1 transaksi.
     */
    public function checkTag(Request $request, GateValidationService $validationService, CameraService $cameraService)
    {
        $validated = $request->validate([
            'epc_tag' => 'required_without_all:epc_tags,gate_log_id,gate_log_ids|nullable|string|max:128',
            'epc_tags' => 'required_without_all:epc_tag,gate_log_id,gate_log_ids|nullable|array|min:1|max:50',
            'epc_tags.*' => 'required|string|max:128',
            'photo' => 'nullable|string',
            'gate_log_id' => 'nullable|integer|exists:gate_logs,id',
            'gate_log_ids' => 'nullable|array|min:1|max:50',
            'gate_log_ids.*' => 'required|integer|exists:gate_logs,id',
        ]);

        // Tahap ke-2 (opsional / fallback): melampirkan 1 snapshot kamera ke 1 atau banyak GateLog dalam 1 burst.
        $targetLogIds = ! empty($validated['gate_log_ids'])
            ? array_values(array_unique(array_map('intval', $validated['gate_log_ids'])))
            : (! empty($validated['gate_log_id']) ? [(int) $validated['gate_log_id']] : []);

        if (! empty($targetLogIds) && ! empty($validated['photo'])) {
            $imageData = $this->decodeBase64Image($validated['photo']);

            if ($imageData !== null) {
                $updatedLogs = DB::transaction(function () use ($targetLogIds, $validated, $cameraService, $imageData) {
                    $existingLogs = GateLog::query()
                        ->lockForUpdate()
                        ->whereIn('id', $targetLogIds)
                        ->orderBy('id')
                        ->get();

                    $eligibleLogs = $existingLogs->filter(function (GateLog $log) use ($targetLogIds, $validated) {
                        if ($log->action_status !== GateAction::DENIED || filled($log->capture_image_path)) {
                            return false;
                        }

                        if (count($targetLogIds) === 1 && filled($validated['epc_tag'] ?? null)) {
                            return strcasecmp($log->epc_tag_scanned, (string) $validated['epc_tag']) === 0;
                        }

                        return true;
                    })->values();

                    if ($eligibleLogs->isEmpty()) {
                        return $eligibleLogs;
                    }

                    // Simpan 1 file gambar saja di disk dan tautkan ke seluruh GateLog dalam burst ini.
                    $photoPath = $cameraService->capture($imageData, 'photos/gate');
                    GateLog::query()
                        ->whereIn('id', $eligibleLogs->pluck('id')->all())
                        ->update(['capture_image_path' => $photoPath]);

                    return $eligibleLogs->each(fn (GateLog $log) => $log->setAttribute('capture_image_path', $photoPath));
                });

                if ($updatedLogs->isNotEmpty()) {
                    $dispatchIds = [];
                    $contextsById = [];

                    foreach ($updatedLogs as $updated) {
                        $eligibleContext = Cache::pull("telegram:gate_eligible:{$updated->id}");
                        $alreadySentState = Cache::get("telegram:gate_alert_sent:{$updated->id}");

                        if (
                            config('services.telegram.enabled')
                            && $alreadySentState !== 'photo'
                            && ($eligibleContext !== null || $alreadySentState === 'text')
                        ) {
                            $dispatchIds[] = $updated->id;
                            $contextsById[$updated->id] = is_array($eligibleContext)
                                ? $eligibleContext
                                : (array) Cache::get("telegram:gate_context:{$updated->id}", []);
                        }
                    }

                    if (! empty($dispatchIds)) {
                        dispatch(new SendGateViolationTelegramJob($dispatchIds, $contextsById));
                    }

                    return response()->json([
                        'success' => true,
                        'status' => 'denied',
                        'gate_log_id' => $updatedLogs->first()->id,
                        'gate_log_ids' => $updatedLogs->pluck('id')->all(),
                        'photo_attached' => true,
                        'message' => 'Snapshot foto pelanggaran berhasil dilampirkan.',
                    ], 200);
                }
            }
        }

        if (empty($validated['epc_tags']) && blank($validated['epc_tag'] ?? null)) {
            return response()->json([
                'success' => false,
                'message' => 'Minimal satu EPC tag wajib dikirim.',
            ], 422);
        }

        $epcs = ! empty($validated['epc_tags'])
            ? array_values(array_unique(array_filter(
                array_map(fn ($tag) => trim((string) $tag), $validated['epc_tags']),
                fn (string $tag) => $tag !== ''
            )))
            : [trim((string) $validated['epc_tag'])];

        $validationMap = $validationService->checkTags($epcs);
        $hasDenied = collect($validationMap)->contains(fn (array $res) => ($res['status'] ?? 'denied') === 'denied');

        $sharedPhotoPath = null;
        if ($hasDenied && ! empty($validated['photo'])) {
            $imageData = $this->decodeBase64Image($validated['photo']);
            if ($imageData !== null) {
                $sharedPhotoPath = $cameraService->capture($imageData, 'photos/gate');
            }
        }

        $entries = DB::transaction(function () use ($epcs, $validationMap, $sharedPhotoPath) {
            $created = [];
            foreach ($epcs as $epc) {
                $result = $validationMap[$epc];
                $isDenied = ($result['status'] ?? 'denied') === 'denied';

                $gateLog = GateLog::create([
                    'epc_tag_scanned' => $epc,
                    'action_status' => $result['status'],
                    'capture_image_path' => $isDenied ? $sharedPhotoPath : null,
                ]);

                $created[] = [
                    'epc_tag' => $epc,
                    'gate_log' => $gateLog,
                    'result' => $result,
                    'is_denied' => $isDenied,
                ];
            }

            return $created;
        });

        $deniedEntries = array_values(array_filter($entries, fn (array $e) => $e['is_denied']));
        $allowedEntries = array_values(array_filter($entries, fn (array $e) => ! $e['is_denied']));
        $perTagResults = array_map(fn (array $e) => [
            'epc_tag' => $e['epc_tag'],
            'status' => $e['result']['status'],
            'gate_log_id' => $e['gate_log']->id,
            'item_name' => $e['result']['item_name'] ?? null,
            'message' => $e['is_denied'] ? ($e['result']['reason'] ?? null) : ($e['result']['message'] ?? null),
        ], $entries);

        if (! empty($deniedEntries)) {
            $cooldownSeconds = max(1, (int) config('services.telegram.gate_cooldown_seconds', 15));
            $eligibleIds = [];
            $contextsById = [];

            foreach ($deniedEntries as $entry) {
                $cooldownKey = 'telegram:gate_cooldown:' . strtoupper(trim($entry['epc_tag']));
                if (config('services.telegram.enabled') && Cache::add($cooldownKey, true, now()->addSeconds($cooldownSeconds))) {
                    $logId = $entry['gate_log']->id;
                    $eligibleIds[] = $logId;
                    $contextsById[$logId] = $entry['result'];
                    Cache::put("telegram:gate_context:{$logId}", $entry['result'], now()->addMinutes(5));
                }
            }

            if (! empty($eligibleIds)) {
                $job = new SendGateViolationTelegramJob($eligibleIds, $contextsById);

                if (filled($sharedPhotoPath)) {
                    dispatch($job);
                } else {
                    foreach ($eligibleIds as $logId) {
                        Cache::put("telegram:gate_eligible:{$logId}", $contextsById[$logId], now()->addMinutes(2));
                    }

                    // Fallback singkat apabila kamera tidak menyertakan foto di request awal dan tahap-2 terlambat.
                    if (config('queue.default') !== 'sync') {
                        $job->delay(now()->addSeconds(2));
                    }

                    dispatch($job);
                }
            }

            $deniedGateLogIds = array_map(fn (array $e) => $e['gate_log']->id, $deniedEntries);
            $isSingle = count($entries) === 1;
            $deniedNames = array_values(array_unique(array_filter(array_map(
                fn (array $e) => $e['result']['item_name'] ?? null,
                $deniedEntries
            ))));

            return response()->json([
                'success' => true,
                'status' => 'denied',
                'gate_log_id' => $deniedGateLogIds[0],
                'denied_gate_log_ids' => $deniedGateLogIds,
                'message' => $isSingle
                    ? $deniedEntries[0]['result']['reason']
                    : sprintf('%d dari %d tag ditolak melintasi gerbang.', count($deniedEntries), count($entries)),
                'item_name' => $isSingle
                    ? ($deniedEntries[0]['result']['item_name'] ?? null)
                    : (! empty($deniedNames) ? implode(', ', $deniedNames) : null),
                'trigger_sirene' => true,
                'trigger_capture' => $sharedPhotoPath === null,
                'photo_attached' => $sharedPhotoPath !== null,
                'batch_count' => count($entries),
                'denied_count' => count($deniedEntries),
                'allowed_count' => count($allowedEntries),
                'results' => $perTagResults,
            ], 200);
        }

        $firstEntry = $entries[0];
        $isSingle = count($entries) === 1;

        return response()->json([
            'success' => true,
            'status' => 'allowed',
            'gate_log_id' => $firstEntry['gate_log']->id,
            'denied_gate_log_ids' => [],
            'message' => $isSingle
                ? $firstEntry['result']['message']
                : sprintf('Semua %d tag berstatus "dipinjam" — akses diizinkan.', count($entries)),
            'item_name' => $isSingle
                ? ($firstEntry['result']['item_name'] ?? null)
                : collect($allowedEntries)->pluck('result.item_name')->filter()->implode(', '),
            'batch_count' => count($entries),
            'denied_count' => 0,
            'allowed_count' => count($allowedEntries),
            'results' => $perTagResults,
        ], 200);
    }

    private function decodeBase64Image(string $rawPhoto): ?string
    {
        $decoded = base64_decode(
            preg_replace('#^data:image/\w+;base64,#i', '', $rawPhoto),
            true
        );

        return ($decoded !== false && $decoded !== '') ? $decoded : null;
    }
}
