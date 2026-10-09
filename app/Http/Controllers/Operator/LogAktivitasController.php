<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogAktivitasController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ]);

        $cari = $request->string('q')->trim()->value();

        $log = LogAktivitas::with(['user.operator', 'user.kepsek', 'user.guru', 'user.siswa'])
            ->when($cari, fn ($q) => $q->whereHas('user', fn ($q) => $q->where('username', 'like', "%{$cari}%")))
            ->when($request->filled('aksi'), fn ($q) => $q->where('aksi', $request->aksi))
            ->when($request->filled('role'), fn ($q) => $q->whereHas('user', fn ($q) => $q->where('role', $request->role)))
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('created_at', '>=', $request->dari))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('created_at', '<=', $request->sampai))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        $daftarAksi = LogAktivitas::distinct()->orderBy('aksi')->pluck('aksi');

        return view('operator.log.index', compact('log', 'cari', 'daftarAksi'));
    }
}
