<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Services\AbsensiGuruService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Absen masuk & keluar kelas sesuai jadwal mengajar hari ini, beserta riwayat bulan berjalan. */
class AbsensiController extends Controller
{
    public function __construct(private AbsensiGuruService $service) {}

    public function index(Request $request): View
    {
        $guru = $request->user()->guru;

        return view('guru.absensi', [
            'jadwalHariIni' => $this->service->jadwalHariIni($guru),
            'riwayat' => $guru->absensi()
                ->with(['jadwal.pengampu.kelas', 'jadwal.pengampu.mapel'])
                ->whereBetween('tanggal', [now()->startOfMonth()->toDateString(), today()->toDateString()])
                ->latest('tanggal')
                ->latest('jam_masuk')
                ->get(),
            'toleransi' => $this->service->toleransi(),
        ]);
    }

    public function masuk(Request $request, Jadwal $jadwal): RedirectResponse
    {
        $absensi = $this->service->masuk($request->user()->guru, $jadwal, $request->ip());

        return back()->with('success', "Absen masuk kelas {$jadwal->pengampu->kelas->nama_kelas} tercatat pukul ".substr($absensi->jam_masuk, 0, 5).($absensi->status === 'terlambat' ? ' (terlambat).' : '.'));
    }

    public function keluar(Request $request, Jadwal $jadwal): RedirectResponse
    {
        $absensi = $this->service->keluar($request->user()->guru, $jadwal, $request->ip());

        return back()->with('success', "Absen keluar kelas {$jadwal->pengampu->kelas->nama_kelas} tercatat pukul ".substr($absensi->jam_keluar, 0, 5).'.');
    }
}
