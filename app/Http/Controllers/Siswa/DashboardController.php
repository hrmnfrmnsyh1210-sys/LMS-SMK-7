<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Pengumuman;
use App\Models\Tugas;
use App\Services\AkademikSiswaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public const HARI = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    public function __invoke(Request $request, AkademikSiswaService $akademik): View
    {
        $user = $request->user();
        $siswa = $user->siswa;
        $kelas = $siswa->kelasPada();
        $pengampuIds = $siswa->pengampu()->pluck('id');

        $jadwalHariIni = Jadwal::with(['pengampu.mapel', 'pengampu.guru'])
            ->whereIn('pengampu_id', $pengampuIds)
            ->where('hari', self::HARI[now()->dayOfWeekIso])
            ->orderBy('jam_mulai')
            ->get();

        $deadline = Tugas::whereIn('pengampu_id', $pengampuIds)
            ->whereBetween('deadline', [now()->startOfMonth(), now()->endOfMonth()])
            ->pluck('deadline')
            ->map(fn ($d) => $d->toDateString())
            ->all();

        return view('siswa.dashboard', [
            'user' => $user,
            'siswa' => $siswa,
            'kelas' => $kelas,
            'jadwalHariIni' => $jadwalHariIni,
            'tugasMendatang' => $akademik->tugasBelumQuery($siswa, $pengampuIds)->limit(5)->get(),
            'kuisAktif' => $akademik->kuisAktifQuery($siswa, $pengampuIds)->limit(4)->get(),
            'statistik' => $akademik->ringkasan($siswa),
            'pengumuman' => Pengumuman::untukSiswa($kelas?->id)->latest()->limit(3)->get(),
            'deadline' => $deadline,
        ]);
    }
}
