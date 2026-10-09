<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\JawabanKuis;
use App\Models\KelasSiswa;
use App\Models\Pengampu;
use App\Models\PengumpulanTugas;
use App\Models\Pengumuman;
use App\Models\SuratIzin;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const HARI = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    public function __invoke(Request $request): View
    {
        $guru = $request->user()->guru;
        $pengampuIds = Pengampu::tahunAktif()->where('guru_id', $guru->id)->pluck('id');

        $jadwalHariIni = Jadwal::with(['pengampu.mapel', 'pengampu.kelas'])
            ->whereIn('pengampu_id', $pengampuIds)
            ->where('hari', self::HARI[now()->dayOfWeekIso])
            ->orderBy('jam_mulai')
            ->get();

        $perluDinilai = PengumpulanTugas::with(['siswa', 'tugas.pengampu.mapel', 'tugas.pengampu.kelas'])
            ->whereNull('nilai')
            ->whereHas('tugas', fn ($q) => $q->whereIn('pengampu_id', $pengampuIds))
            ->oldest('waktu_kumpul')
            ->limit(6)
            ->get();

        $kelasWali = $guru->kelasWaliAktif();

        $statistik = [
            'kelas' => $pengampuIds->count(),
            'siswa' => KelasSiswa::where('tahun_ajaran_id', TahunAjaran::aktifId())
                ->whereIn('kelas_id', Pengampu::whereIn('id', $pengampuIds)->select('kelas_id'))
                ->distinct()
                ->count('siswa_id'),
            'perlu_dinilai' => PengumpulanTugas::whereNull('nilai')
                ->whereHas('tugas', fn ($q) => $q->whereIn('pengampu_id', $pengampuIds))->count(),
            'esai_menunggu' => JawabanKuis::whereNotNull('waktu_submit')->whereNull('total_skor')
                ->whereHas('kuis', fn ($q) => $q->whereIn('pengampu_id', $pengampuIds))->count(),
            'surat_menunggu' => $kelasWali ? SuratIzin::where('kelas_id', $kelasWali->id)->where('status', 'menunggu')->count() : 0,
        ];

        $kelasIds = Pengampu::whereIn('id', $pengampuIds)->pluck('kelas_id')->push($kelasWali?->id)->filter();
        $pengumuman = Pengumuman::with('pembuat')
            ->where(fn ($q) => $q->whereIn('target', ['semua', 'guru'])
                ->orWhere(fn ($q) => $q->where('target', 'kelas')->whereIn('kelas_id', $kelasIds)))
            ->latest()
            ->limit(4)
            ->get();

        $deadline = Tugas::whereIn('pengampu_id', $pengampuIds)
            ->whereBetween('deadline', [now()->startOfMonth(), now()->endOfMonth()])
            ->pluck('deadline')
            ->map(fn ($d) => $d->toDateString())
            ->all();

        $notifikasi = $request->user()->unreadNotifications()->latest()->limit(5)->get();

        return view('guru.dashboard', compact(
            'jadwalHariIni', 'perluDinilai', 'statistik', 'pengumuman', 'deadline', 'kelasWali', 'notifikasi'
        ));
    }
}
