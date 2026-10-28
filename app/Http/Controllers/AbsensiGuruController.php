<?php

namespace App\Http\Controllers;

use App\Services\AbsensiGuruService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Pantauan absensi guru per sesi mengajar untuk operator dan kepala sekolah (route berprefiks role, baca-saja). */
class AbsensiGuruController extends Controller
{
    public function __invoke(Request $request, AbsensiGuruService $service): View
    {
        $mode = $request->input('mode') === 'bulanan' ? 'bulanan' : 'harian';
        $cari = $request->string('q')->trim()->value();
        $tanggal = rescue(fn () => Carbon::parse($request->input('tanggal', today()->toDateString()))->startOfDay(), today(), false);
        $bulan = rescue(fn () => Carbon::createFromFormat('Y-m', $request->input('bulan', now()->format('Y-m')))->startOfMonth(), now()->startOfMonth(), false);

        return view('absensi-guru.index', [
            'mode' => $mode,
            'cari' => $cari,
            'tanggal' => $tanggal,
            'bulan' => $bulan,
            'rekap' => $mode === 'bulanan' ? $service->rekapBulanan($bulan, $cari) : $service->rekapHarian($tanggal, $cari),
            'toleransi' => $service->toleransi(),
            'prefix' => $request->user()->role,
        ]);
    }
}
