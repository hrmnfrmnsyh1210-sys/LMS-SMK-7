<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Pengumuman;
use App\Models\Presensi;
use App\Services\AkademikSiswaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Halaman baca-saja siswa: jadwal, presensi, nilai, dan pengumuman. */
class AkademikController extends Controller
{
    public function __construct(private AkademikSiswaService $akademik) {}

    public function jadwal(Request $request): View
    {
        $siswa = $request->user()->siswa;

        return view('siswa.jadwal', ['jadwal' => $this->akademik->jadwal($siswa), 'kelas' => $siswa->kelasPada()]);
    }

    public function presensi(Request $request): View
    {
        $siswa = $request->user()->siswa;
        $pengampuIds = $siswa->pengampu()->pluck('id');
        $hariIni = Jadwal::hariDari(today());

        $jadwalHariIni = $hariIni ? Jadwal::with(['pengampu.mapel', 'pengampu.guru'])
            ->whereIn('pengampu_id', $pengampuIds)
            ->where('hari', $hariIni)
            ->orderBy('jam_mulai')
            ->get() : collect();

        $presensiHariIni = Presensi::where('siswa_id', $siswa->id)
            ->whereIn('pengampu_id', $pengampuIds)
            ->whereDate('tanggal', today())
            ->get()
            ->keyBy('pengampu_id');

        return view('siswa.presensi', $this->akademik->presensi($siswa) + [
            'jadwalHariIni' => $jadwalHariIni,
            'presensiHariIni' => $presensiHariIni,
        ]);
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
