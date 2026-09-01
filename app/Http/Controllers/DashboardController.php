<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tampilkan dasbor statistik utama.
     */
    public function index()
    {
        return view('dashboard');
    }
}
