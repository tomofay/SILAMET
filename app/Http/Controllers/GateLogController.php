<?php

namespace App\Http\Controllers;

use App\Models\GateLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GateLogController extends Controller
{
    /**
     * Tampilkan daftar semua gate logs.
     */
    public function index(Request $request)
    {
        $query = GateLog::with(['item'])->latest('created_at')->latest('id');

        if ($request->filled('status') && in_array($request->status, ['allowed', 'denied'], true)) {
            $query->where('action_status', $request->status);
        }

        $logs = $query->paginate(20)->withQueryString();

        $stats = $this->computeStats();
        $latestLogId = (int) (GateLog::max('id') ?? 0);
        $latestDeniedId = (int) (GateLog::where('action_status', 'denied')->max('id') ?? 0);

        return view('gate-logs.index', compact('logs', 'stats', 'latestLogId', 'latestDeniedId'));
    }

    /**
     * Feed JSON ringan untuk polling live auto-refresh halaman /gate-logs.
     */
    public function feed(Request $request)
    {
        $status = $request->input('status');
        $afterId = (int) $request->input('after_id', 0);
        $afterDeniedId = (int) $request->input('after_denied_id', 0);

        $latestId = (int) (GateLog::max('id') ?? 0);
        $latestDeniedId = (int) (GateLog::where('action_status', 'denied')->max('id') ?? 0);
        $stats = $this->computeStats();

        $changed = $latestId !== $afterId || $request->boolean('force');
        $tbodyHtml = null;

        if ($changed) {
            $query = GateLog::with(['item'])->latest('created_at')->latest('id');
            if ($status !== null && in_array($status, ['allowed', 'denied'], true)) {
                $query->where('action_status', $status);
            }

            $logs = $query->paginate(20);
            $tbodyHtml = view('gate-logs.partials._rows', compact('logs'))->render();
        }

        $latestDeniedAlert = null;
        if ($latestDeniedId > $afterDeniedId && $latestDeniedId > 0) {
            $newestDenied = GateLog::with(['item'])->find($latestDeniedId);
            if ($newestDenied) {
                $activeItem = ($newestDenied->item && ! $newestDenied->item->trashed())
                    ? $newestDenied->item
                    : null;

                $latestDeniedAlert = [
                    'id' => $newestDenied->id,
                    'epc' => $newestDenied->epc_tag_scanned,
                    'item_name' => $activeItem?->name ?? 'Tag Asing / Tidak Terdaftar',
                    'time' => optional($newestDenied->created_at)->format('d/m/Y H:i:s'),
                    'photo_url' => $newestDenied->capture_image_path
                        ? route('gate-logs.photo', $newestDenied->id)
                        : null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'changed' => $changed,
            'latest_id' => $latestId,
            'latest_denied_id' => $latestDeniedId,
            'stats' => $stats,
            'tbody_html' => $tbodyHtml,
            'latest_denied_alert' => $latestDeniedAlert,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function computeStats(): array
    {
        return [
            'total' => GateLog::count(),
            'allowed' => GateLog::where('action_status', 'allowed')->count(),
            'denied' => GateLog::where('action_status', 'denied')->count(),
            'today_denied' => GateLog::where('action_status', 'denied')->whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Tampilkan detail log tertentu.
     */
    public function show(string $id)
    {
        $log = GateLog::with(['item'])->findOrFail($id);

        return view('gate-logs.show', compact('log'));
    }

    /**
     * Sajikan foto snapshot pelanggar secara aman langsung dari storage publik
     * (tahan terhadap symlink/junction Windows yang terputus dan terlindungi auth).
     */
    public function photo(string $id)
    {
        $log = GateLog::findOrFail($id);
        $path = ltrim((string) $log->capture_image_path, '/\\');

        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            $contents = $disk->get($path);

            if ($contents !== null && $contents !== '') {
                return response($contents, 200, [
                    'Content-Type' => $this->detectImageMime($contents, $path),
                    'Cache-Control' => 'private, max-age=3600',
                ]);
            }
        }

        $publicFallback = public_path('storage/' . $path);
        if (is_file($publicFallback)) {
            $contents = @file_get_contents($publicFallback);
            if ($contents !== false && $contents !== '') {
                return response($contents, 200, [
                    'Content-Type' => $this->detectImageMime($contents, $path),
                    'Cache-Control' => 'private, max-age=3600',
                ]);
            }
        }

        abort(404);
    }

    private function detectImageMime(string $binary, string $path): string
    {
        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }

        if (str_starts_with($binary, 'GIF87a') || str_starts_with($binary, 'GIF89a')) {
            return 'image/gif';
        }

        if (strlen($binary) >= 12 && substr($binary, 0, 4) === 'RIFF' && substr($binary, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }
}
