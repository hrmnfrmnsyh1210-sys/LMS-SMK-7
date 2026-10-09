<?php

namespace App\Services;

use App\Models\BobotNilai;
use App\Models\Kuis;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Tugas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rekap nilai per pengampu.
 *
 * - nilai_tugas = rata-rata (nilai / nilai_maks × 100) seluruh tugas. Tugas yang
 *   tidak dikumpulkan setelah deadline dihitung 0; yang belum lewat deadline atau
 *   belum dinilai tidak dihitung.
 * - nilai_kuis  = rata-rata total_skor kuis berjenis "kuis" dengan aturan serupa.
 * - nilai_uts/uas diisi manual atau diambil dari kuis berjenis UTS/UAS.
 * - nilai_akhir = Σ(komponen × bobot) / 100, komponen kosong dihitung 0.
 */
class NilaiService
{
    /**
     * Hitung ulang nilai tugas, kuis, dan akhir seluruh siswa; UTS/UAS dipertahankan
     * kecuali diberikan pada $utsUas (siswa_id => ['nilai_uts' => .., 'nilai_uas' => ..]).
     *
     * @param  array<int|string, array{nilai_uts?: mixed, nilai_uas?: mixed}>  $utsUas
     * @return Collection<int, Nilai>
     */
    public function hitungRekap(Pengampu $pengampu, array $utsUas = []): Collection
    {
        $siswa = $pengampu->daftarSiswa();
        $bobot = $pengampu->bobot();
        $tugas = $pengampu->tugas()->with('pengumpulan')->get();
        $kuis = $pengampu->kuis()->where('jenis', 'kuis')->with('jawaban')->get();
        $nilaiLama = $pengampu->nilai()->get()->keyBy('siswa_id');

        return DB::transaction(function () use ($pengampu, $siswa, $bobot, $tugas, $kuis, $nilaiLama, $utsUas) {
            return $siswa->map(function ($s) use ($pengampu, $bobot, $tugas, $kuis, $nilaiLama, $utsUas) {
                $lama = $nilaiLama->get($s->id);
                $input = $utsUas[$s->id] ?? [];

                $data = [
                    'nilai_tugas' => $this->rataTugas($tugas, $s->id),
                    'nilai_kuis' => $this->rataKuis($kuis, $s->id),
                    'nilai_uts' => array_key_exists('nilai_uts', $input) ? $this->angka($input['nilai_uts']) : $lama?->nilai_uts,
                    'nilai_uas' => array_key_exists('nilai_uas', $input) ? $this->angka($input['nilai_uas']) : $lama?->nilai_uas,
                ];
                $data['nilai_akhir'] = $this->nilaiAkhir($data, $bobot);

                return Nilai::updateOrCreate(['pengampu_id' => $pengampu->id, 'siswa_id' => $s->id], $data)
                    ->setRelation('siswa', $s);
            });
        });
    }

    /**
     * Isi nilai UTS/UAS dari kuis berjenis UTS/UAS (kuis terbaru per jenis).
     *
     * @return array{uts: bool, uas: bool} jenis yang berhasil diambil
     */
    public function ambilUtsUasDariKuis(Pengampu $pengampu): array
    {
        $hasil = ['uts' => false, 'uas' => false];
        $utsUas = [];

        foreach (['uts', 'uas'] as $jenis) {
            $kuis = $pengampu->kuis()->where('jenis', $jenis)->with('jawaban')->latest('waktu_mulai')->first();
            if (! $kuis) {
                continue;
            }

            $hasil[$jenis] = true;
            foreach ($pengampu->daftarSiswa() as $s) {
                $skor = $this->skorKuis($kuis, $s->id);
                // Biarkan nilai lama bila siswa belum bisa dinilai (mis. esai belum dikoreksi).
                if ($skor !== false) {
                    $utsUas[$s->id]["nilai_{$jenis}"] = $skor;
                }
            }
        }

        $this->hitungRekap($pengampu, $utsUas);

        return $hasil;
    }

    /** @param array{nilai_tugas: mixed, nilai_kuis: mixed, nilai_uts: mixed, nilai_uas: mixed} $nilai */
    public function nilaiAkhir(array $nilai, BobotNilai $bobot): ?float
    {
        $komponen = ['tugas', 'kuis', 'uts', 'uas'];
        if (collect($komponen)->every(fn ($k) => $nilai["nilai_{$k}"] === null)) {
            return null;
        }

        $total = collect($komponen)->sum(fn ($k) => (float) $nilai["nilai_{$k}"] * $bobot->{"bobot_{$k}"});

        return round($total / 100, 2);
    }

    /** @param Collection<int, Tugas> $tugas */
    private function rataTugas(Collection $tugas, int $siswaId): ?float
    {
        $skor = $tugas->map(function (Tugas $t) use ($siswaId) {
            $p = $t->pengumpulan->firstWhere('siswa_id', $siswaId);
            if ($p && $p->nilai !== null) {
                return (float) $p->nilai / max(1, $t->nilai_maks) * 100;
            }

            return ! $p && $t->sudahLewatDeadline() ? 0.0 : null;
        })->filter(fn ($v) => $v !== null);

        return $skor->isEmpty() ? null : round($skor->avg(), 2);
    }

    /** @param Collection<int, Kuis> $kuis */
    private function rataKuis(Collection $kuis, int $siswaId): ?float
    {
        $skor = $kuis->map(fn (Kuis $k) => $this->skorKuis($k, $siswaId))
            ->filter(fn ($v) => $v !== false && $v !== null);

        return $skor->isEmpty() ? null : round($skor->avg(), 2);
    }

    /** Skor siswa pada satu kuis; false bila belum bisa dinilai. */
    private function skorKuis(Kuis $kuis, int $siswaId): float|false
    {
        $j = $kuis->jawaban->firstWhere('siswa_id', $siswaId);
        if ($j && $j->total_skor !== null) {
            return (float) $j->total_skor;
        }

        return ! $j && now()->gt($kuis->waktu_selesai) ? 0.0 : false;
    }

    private function angka(mixed $nilai): ?float
    {
        return $nilai === null || $nilai === '' ? null : round((float) $nilai, 2);
    }
}
