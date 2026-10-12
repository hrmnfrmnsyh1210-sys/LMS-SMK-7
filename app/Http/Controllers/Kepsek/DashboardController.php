<?php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Kuis;
use App\Models\LogAktivitas;
use App\Models\Mapel;
use App\Models\Materi;
use App\Models\Pengampu;
use App\Models\Pengumuman;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Services\GrafikService;
use App\Services\MonitoringSekolahService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(MonitoringSekolahService $monitoring, GrafikService $grafik): View
    {
        $pengampuIds = Pengampu::tahunAktif()->select('id');
        $presensi = $monitoring->komposisiPresensi();
        $totalPresensi = array_sum($presensi);
        $kelas = $monitoring->rekapKelas();

        $statistik = [
            'siswa' => Siswa::where('status', 'aktif')->count(),
            'guru' => Guru::count(),
            'kelas' => Kelas::tahunAktif()->count(),
            'mapel' => Mapel::count(),
            'kehadiran' => $totalPresensi ? round($presensi['hadir'] / $totalPresensi * 100, 1) : null,
            'rata' => $kelas->whereNotNull('rata')->isEmpty() ? null : round($kelas->whereNotNull('rata')->avg('rata'), 2),
            'materi' => Materi::whereIn('pengampu_id', $pengampuIds)->count(),
            'tugas' => Tugas::whereIn('pengampu_id', $pengampuIds)->count(),
            'kuis' => Kuis::whereIn('pengampu_id', $pengampuIds)->count(),
        ];

        $warna = ['hadir' => '#7cc9a5', 'izin' => '#7fcbdc', 'sakit' => '#f2c27b', 'alpa' => '#ee9a9a'];
        $grafikPresensi = collect($presensi)->map(fn ($n, $st) => ['label' => ucfirst($st), 'nilai' => $n, 'warna' => $warna[$st]])->values()->all();

        return view('kepsek.dashboard', [
            'statistik' => $statistik,
            'grafikPresensi' => $grafikPresensi,
            'grafik' => [
                'kehadiran' => $grafik->trenKehadiran(Pengampu::tahunAktif()->pluck('id')),
                'nilaiKelas' => $grafik->rataNilaiPerKelas(),
                'capaian' => $grafik->capaian(Pengampu::tahunAktif()->pluck('id')),
            ],
            'kkm' => (float) pengaturan('kkm', 75),
            'kelasTerbaik' => $kelas->whereNotNull('rata')->sortByDesc('rata')->take(5)->values(),
            'pengumuman' => Pengumuman::latest()->limit(4)->get(),
            'aktivitasGuru' => LogAktivitas::with('user.guru')
                ->whereHas('user', fn ($q) => $q->where('role', 'guru'))
                ->latest('created_at')
                ->limit(6)
                ->get(),
        ]);
    }
}
