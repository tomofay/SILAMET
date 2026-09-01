<?php

namespace App\Http\Controllers;

use App\Models\GateLog;
use Illuminate\Http\Request;

class GateLogController extends Controller
{
    /**
     * Tampilkan daftar semua gate logs.
     */
    public function index()
    {
        $logs = GateLog::with(['item'])->latest()->paginate(20);
        return view('gate-logs.index', compact('logs'));
    }

    /**
     * Tampilkan detail log tertentu.
     */
    public function show(string $id)
    {
        $log = GateLog::findOrFail($id);
        return view('gate-logs.show', compact('log'));
    }
}
