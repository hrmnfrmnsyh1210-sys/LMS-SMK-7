<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Kuis;
use App\Models\Materi;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Presensi;
use App\Models\Tugas;
use Illuminate\Support\Collection;

/** Ringkasan sekolah untuk kepala sekolah pada tahun ajaran aktif. */
class MonitoringSekolahService
{
    /**
     * Keaktifan guru: jumlah kelas ajar, materi, tugas, kuis, dan pertemuan presensi.
     *
     * @return Collection<int, array{guru: Guru, kelas: int, materi: int, tugas: int, kuis: int, pertemuan: int}>
     */
    public function rekapGuru(?string $cari = null): Collection
    {
        $pengampu = Pengampu::tahunAktif()->get(['id', 'guru_id']);
        $guruPengampu = $pengampu->groupBy('guru_id')->map->pluck('id');
        $ids = $pengampu->pluck('id');

        $materi = Materi::whereIn('pengampu_id', $ids)->selectRaw('pengampu_id, COUNT(*) as jumlah')->groupBy('pengampu_id')->pluck('jumlah', 'pengampu_id');
        $tugas = Tugas::whereIn('pengampu_id', $ids)->selectRaw('pengampu_id, COUNT(*) as jumlah')->groupBy('pengampu_id')->pluck('jumlah', 'pengampu_id');
        $kuis = Kuis::whereIn('pengampu_id', $ids)->selectRaw('pengampu_id, COUNT(*) as jumlah')->groupBy('pengampu_id')->pluck('jumlah', 'pengampu_id');
        $pertemuan = Presensi::whereIn('pengampu_id', $ids)->selectRaw('pengampu_id, COUNT(DISTINCT pertemuan_ke) as jumlah')->groupBy('pengampu_id')->pluck('jumlah', 'pengampu_id');

        $jumlah = fn (Collection $hitung, Collection $milik) => (int) $milik->sum(fn ($id) => $hitung[$id] ?? 0);

        return Guru::with('user')
            ->when($cari, fn ($q) => $q->where(fn ($q) => $q->where('nama', 'like', "%{$cari}%")->orWhere('nip', 'like', "%{$cari}%")))
            ->orderBy('nama')
            ->get()
            ->map(function (Guru $g) use ($guruPengampu, $materi, $tugas, $kuis, $pertemuan, $jumlah) {
                $milik = $guruPengampu->get($g->id, collect());

                return [
                    'guru' => $g,
                    'kelas' => $milik->count(),
                    'materi' => $jumlah($materi, $milik),
                    'tugas' => $jumlah($tugas, $milik),
                    'kuis' => $jumlah($kuis, $milik),
                    'pertemuan' => $jumlah($pertemuan, $milik),
                ];
            });
    }

    /**
     * Per kelas: jumlah siswa, persentase hadir, dan rata-rata nilai akhir.
     *
     * @return Collection<int, array{kelas: Kelas, siswa: int, kehadiran: ?float, rata: ?float}>
     */
    public function rekapKelas(): Collection
    {
        $kelas = Kelas::tahunAktif()->with(['jurusan', 'waliKelas'])
            ->withCount(['siswa' => fn ($q) => $q->where('siswa.status', 'aktif')])
            ->orderBy('nama_kelas')
            ->get();

        $presensi = Presensi::join('pengampu', 'pengampu.id', '=', 'presensi.pengampu_id')
            ->whereIn('pengampu.kelas_id', $kelas->pluck('id'))
            ->selectRaw("pengampu.kelas_id, COUNT(*) as total, SUM(presensi.status = 'hadir') as hadir")
            ->groupBy('pengampu.kelas_id')
            ->get()
            ->keyBy('kelas_id');

        $nilai = Nilai::join('pengampu', 'pengampu.id', '=', 'nilai.pengampu_id')
            ->whereIn('pengampu.kelas_id', $kelas->pluck('id'))
            ->whereNotNull('nilai.nilai_akhir')
            ->selectRaw('pengampu.kelas_id, AVG(nilai.nilai_akhir) as rata')
            ->groupBy('pengampu.kelas_id')
            ->pluck('rata', 'kelas_id');

        return $kelas->map(fn (Kelas $k) => [
            'kelas' => $k,
            'siswa' => $k->siswa_count,
            'kehadiran' => ($p = $presensi->get($k->id)) && $p->total ? round($p->hadir / $p->total * 100, 1) : null,
            'rata' => isset($nilai[$k->id]) ? round((float) $nilai[$k->id], 2) : null,
        ]);
    }

    /** @return array<string, int> status => jumlah presensi tahun aktif */
    public function komposisiPresensi(): array
    {
        $hitung = Presensi::whereIn('pengampu_id', Pengampu::tahunAktif()->select('id'))
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return collect(array_keys(PresensiService::STATUS))->mapWithKeys(fn ($st) => [$st => (int) ($hitung[$st] ?? 0)])->all();
    }
}
