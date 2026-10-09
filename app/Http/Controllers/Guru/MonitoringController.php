<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Services\NilaiService;
use App\Services\PresensiService;
use App\Services\RankingService;
use Illuminate\View\View;

/** Monitoring perkembangan nilai & peringkat siswa di kelas ajar. */
class MonitoringController extends Controller
{
    public function index(Pengampu $pengampu, NilaiService $nilai, RankingService $ranking, PresensiService $presensi): View
    {
        $nilai->hitungRekap($pengampu);
        $kehadiran = $presensi->rekap($pengampu)->keyBy(fn ($r) => $r['siswa']->id);

        return view('guru.monitoring.index', [
            'pengampu' => $pengampu,
            'ranking' => $ranking->pengampu($pengampu),
            'kehadiran' => $kehadiran,
            'kkm' => (float) pengaturan('kkm', 75),
        ]);
    }

    /** Grafik perkembangan nilai tugas & kuis seorang siswa. */
    public function siswa(Pengampu $pengampu, Siswa $siswa): View
    {
        abort_unless($pengampu->daftarSiswa()->contains('id', $siswa->id), 404);

        $tugas = $pengampu->tugas()->with(['pengumpulan' => fn ($q) => $q->where('siswa_id', $siswa->id)])->orderBy('deadline')->get();
        $kuis = $pengampu->kuis()->with(['jawaban' => fn ($q) => $q->where('siswa_id', $siswa->id)])->orderBy('waktu_mulai')->get();

        $riwayat = collect()
            ->merge($tugas->map(fn ($t) => [
                'tanggal' => $t->deadline,
                'jenis' => 'Tugas',
                'judul' => $t->judul,
                'nilai' => ($p = $t->pengumpulan->first()) && $p->nilai !== null ? round($p->nilai / max(1, $t->nilai_maks) * 100, 2) : null,
                'keterangan' => ! $p ? 'Belum dikumpulkan' : ($p->nilai === null ? 'Belum dinilai' : null),
            ]))
            ->merge($kuis->map(fn ($k) => [
                'tanggal' => $k->waktu_mulai,
                'jenis' => $k->label_jenis,
                'judul' => $k->judul,
                'nilai' => ($j = $k->jawaban->first()) && $j->total_skor !== null ? (float) $j->total_skor : null,
                'keterangan' => ! $j ? 'Belum dikerjakan' : ($j->total_skor === null ? 'Menunggu koreksi' : null),
            ]))
            ->sortBy('tanggal')
            ->values();

        return view('guru.monitoring.siswa', [
            'pengampu' => $pengampu,
            'siswa' => $siswa,
            'riwayat' => $riwayat,
            'nilai' => $pengampu->nilai()->where('siswa_id', $siswa->id)->first(),
            'kkm' => (float) pengaturan('kkm', 75),
        ]);
    }
}
