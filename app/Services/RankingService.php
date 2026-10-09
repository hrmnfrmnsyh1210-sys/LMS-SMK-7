<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Siswa;
use Illuminate\Support\Collection;

/**
 * Ranking = urutan rata-rata nilai akhir. Nilai sama mendapat peringkat sama
 * (1, 2, 2, 4).
 */
class RankingService
{
    /**
     * Ranking kelas: rata-rata nilai akhir semua mapel siswa di kelas.
     *
     * @return Collection<int, array{siswa: Siswa, rata: ?float, jumlah_mapel: int, peringkat: ?int}>
     */
    public function kelas(Kelas $kelas): Collection
    {
        $siswa = $kelas->siswa()->where('siswa.status', 'aktif')->orderBy('nama')->get();
        $pengampuIds = $kelas->pengampu()->pluck('id');

        $nilai = Nilai::whereIn('pengampu_id', $pengampuIds)
            ->whereNotNull('nilai_akhir')
            ->get()
            ->groupBy('siswa_id');

        $baris = $siswa->map(fn (Siswa $s) => [
            'siswa' => $s,
            'rata' => isset($nilai[$s->id]) ? round($nilai[$s->id]->avg('nilai_akhir'), 2) : null,
            'jumlah_mapel' => isset($nilai[$s->id]) ? $nilai[$s->id]->count() : 0,
        ]);

        return $this->beriPeringkat($baris);
    }

    /**
     * Ranking siswa pada satu mapel (pengampu) berdasarkan nilai akhir.
     *
     * @return Collection<int, array{siswa: Siswa, rata: ?float, nilai: ?Nilai, peringkat: ?int}>
     */
    public function pengampu(Pengampu $pengampu): Collection
    {
        $nilai = $pengampu->nilai()->get()->keyBy('siswa_id');

        $baris = $pengampu->daftarSiswa()->map(fn (Siswa $s) => [
            'siswa' => $s,
            'nilai' => $nilai->get($s->id),
            'rata' => $nilai->get($s->id)?->nilai_akhir !== null ? (float) $nilai->get($s->id)->nilai_akhir : null,
        ]);

        return $this->beriPeringkat($baris);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $baris  setiap baris punya kunci 'rata'
     * @return Collection<int, array<string, mixed>>
     */
    private function beriPeringkat(Collection $baris): Collection
    {
        $urut = $baris->sortByDesc(fn ($b) => $b['rata'] ?? -1)->values();

        $peringkat = 0;
        $sebelumnya = null;

        return $urut->map(function ($b, $i) use (&$peringkat, &$sebelumnya) {
            if ($b['rata'] === null) {
                return $b + ['peringkat' => null];
            }
            if ($b['rata'] !== $sebelumnya) {
                $peringkat = $i + 1;
                $sebelumnya = $b['rata'];
            }

            return $b + ['peringkat' => $peringkat];
        });
    }
}
