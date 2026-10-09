<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Presensi;
use Illuminate\Support\Collection;

/** Rekap satu kelas: nilai akhir semua mapel, kehadiran, dan ranking (dipakai wali kelas & kepala sekolah). */
class RekapKelasService
{
    public function __construct(private RankingService $ranking) {}

    /**
     * @return array{kelas: Kelas, pengampu: Collection<int, Pengampu>, ranking: Collection<int, array<string, mixed>>, nilai: Collection<int, Collection<int, Nilai>>, kehadiran: Collection<int, Collection<string, int>>, kkm: float}
     */
    public function rekap(Kelas $kelas): array
    {
        $kelas->load(['pengampu.mapel', 'pengampu.guru', 'jurusan', 'waliKelas']);

        $pengampu = $kelas->pengampu->sortBy('mapel.nama_mapel')->values();
        $nilai = Nilai::whereIn('pengampu_id', $pengampu->pluck('id'))->get()->groupBy('siswa_id');

        $kehadiran = Presensi::whereIn('pengampu_id', $pengampu->pluck('id'))
            ->selectRaw('siswa_id, status, COUNT(*) as jumlah')
            ->groupBy('siswa_id', 'status')
            ->get()
            ->groupBy('siswa_id')
            ->map(fn ($baris) => $baris->pluck('jumlah', 'status'));

        return [
            'kelas' => $kelas,
            'pengampu' => $pengampu,
            'ranking' => $this->ranking->kelas($kelas),
            'nilai' => $nilai,
            'kehadiran' => $kehadiran,
            'kkm' => (float) pengaturan('kkm', 75),
        ];
    }
}
