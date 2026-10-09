<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Arahkan user ke dashboard sesuai role. */
    public function redirect(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->dashboardRoute());
    }

    /**
     * Dashboard sementara untuk kepsek/guru/siswa
     * (fitur lengkap dikerjakan pada fase berikutnya).
     */
    public function role(Request $request): View
    {
        return view('dashboard.role', ['user' => $request->user()]);
    }
}
