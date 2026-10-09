<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Mapel;
use App\Models\Pengampu;
use App\Models\Pengumuman;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Warna irisan grafik donat komposisi jurusan. */
    private const WARNA_JURUSAN = ['#7f9ce0', '#7cc9a5', '#f2c27b', '#ee9a9a', '#b29ced', '#7fcbdc', '#eba3c8'];

    public function __invoke(): View
    {
        $tahunId = TahunAjaran::aktifId();

        $statistik = [
            'siswa' => Siswa::where('status', 'aktif')->count(),
            'guru' => Guru::count(),
            'kelas' => Kelas::where('tahun_ajaran_id', $tahunId)->count(),
            'mapel' => Mapel::count(),
            'pengampu' => Pengampu::where('tahun_ajaran_id', $tahunId)->count(),
            'siswa_tanpa_kelas' => Siswa::where('status', 'aktif')
                ->whereDoesntHave('kelasSiswa', fn ($q) => $q->where('tahun_ajaran_id', $tahunId))
                ->count(),
        ];

        // Jumlah siswa per jurusan pada tahun ajaran aktif.
        $komposisiJurusan = Jurusan::query()
            ->withCount(['kelas as jumlah_siswa' => fn ($q) => $q
                ->where('kelas.tahun_ajaran_id', $tahunId)
                ->join('kelas_siswa', 'kelas_siswa.kelas_id', '=', 'kelas.id')])
            ->orderBy('kode')
            ->get()
            ->values()
            ->map(fn ($j, $i) => [
                'label' => $j->kode.' — '.$j->nama,
                'nilai' => $j->jumlah_siswa,
                'warna' => self::WARNA_JURUSAN[$i % count(self::WARNA_JURUSAN)],
            ])
            ->all();

        $logTerbaru = LogAktivitas::with(['user.operator', 'user.kepsek', 'user.guru', 'user.siswa'])
            ->latest('created_at')
            ->limit(6)
            ->get();

        $pengumumanTerbaru = Pengumuman::latest()->limit(3)->get();

        return view('operator.dashboard', compact('statistik', 'komposisiJurusan', 'logTerbaru', 'pengumumanTerbaru'));
    }
}
