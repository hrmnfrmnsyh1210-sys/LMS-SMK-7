<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\AbsensiGuru;
use App\Models\Jadwal;
use App\Services\AbsensiGuruService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Absen masuk & keluar kelas sesuai jadwal mengajar hari ini, beserta riwayat bulan berjalan. */
class AbsensiController extends Controller
{
    public function __construct(private AbsensiGuruService $service) {}

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('guru.kelas.index');
    }

    public function masuk(Request $request, Jadwal $jadwal): RedirectResponse
    {
        $data = $request->validate([
            'materi' => ['nullable', 'string', 'max:1000'],
            'aktivitas' => ['nullable', 'string', 'max:2000'],
        ]);

        $absensi = $this->service->masuk(
            $request->user()->guru,
            $jadwal,
            $request->ip(),
            $data['materi'] ?? null,
            $data['aktivitas'] ?? null
        );

        return back()->with('success', "Absen masuk kelas {$jadwal->pengampu->kelas->nama_kelas} tercatat pukul ".substr($absensi->jam_masuk, 0, 5).($absensi->status === 'terlambat' ? ' (terlambat).' : '.'));
    }

    public function jurnal(Request $request, AbsensiGuru $absensiGuru): RedirectResponse
    {
        $data = $request->validate([
            'materi' => ['nullable', 'string', 'max:1000'],
            'aktivitas' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->service->simpanJurnal(
            $request->user()->guru,
            $absensiGuru,
            $data['materi'] ?? null,
            $data['aktivitas'] ?? null
        );

        return back()->with('success', 'Jurnal mengajar berhasil diperbarui.');
    }

    public function keluar(Request $request, Jadwal $jadwal): RedirectResponse
    {
        $absensi = $this->service->keluar($request->user()->guru, $jadwal, $request->ip());

        return back()->with('success', "Absen keluar kelas {$jadwal->pengampu->kelas->nama_kelas} tercatat pukul ".substr($absensi->jam_keluar, 0, 5).'.');
    }
}
