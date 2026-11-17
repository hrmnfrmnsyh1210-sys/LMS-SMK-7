<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Pengumuman;
use App\Models\Presensi;
use App\Services\AkademikSiswaService;
use App\Services\PresensiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Halaman baca-saja siswa: jadwal, presensi, nilai, dan pengumuman. */
class AkademikController extends Controller
{
    public function __construct(
        private AkademikSiswaService $akademik,
        private PresensiService $presensiService,
    ) {}

    public function jadwal(Request $request): View
    {
        $siswa = $request->user()->siswa;
        $pengampuIds = $siswa->pengampu()->pluck('id');
        $hariIni = Jadwal::hariDari(today());

        // Presensi hari ini untuk tombol absen mandiri per mata pelajaran.
        $presensiHariIni = Presensi::where('siswa_id', $siswa->id)
            ->whereIn('pengampu_id', $pengampuIds)
            ->whereDate('tanggal', today())
            ->get()
            ->keyBy('pengampu_id');

        $sesiTerbukaPengampuIds = $this->presensiService->daftarSesiTerbuka($pengampuIds);

        return view('siswa.jadwal', array_merge(
            [
                'jadwal' => $this->akademik->jadwal($siswa),
                'kelas' => $siswa->kelasPada(),
                'hariIni' => $hariIni,
                'presensiHariIni' => $presensiHariIni,
                'sesiTerbukaPengampuIds' => $sesiTerbukaPengampuIds,
            ],
            $this->akademik->presensi($siswa),
        ));
    }

    public function presensi(Request $request): RedirectResponse
    {
        // Jadwal dan presensi digabung di halaman jadwal.
        return redirect()->route('siswa.jadwal');
    }

    public function nilai(Request $request): View
    {
        return view('siswa.nilai', $this->akademik->nilai($request->user()->siswa));
    }

    public function pengumuman(Request $request): View
    {
        $pengumuman = Pengumuman::with(['pembuat.guru', 'pembuat.kepsek', 'pembuat.operator', 'kelas'])
            ->untukSiswa($request->user()->siswa->kelasPada()?->id)
            ->latest()
            ->paginate(config('lms.per_page'));

        return view('siswa.pengumuman', compact('pengumuman'));
    }
}
