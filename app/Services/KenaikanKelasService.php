<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KenaikanKelasService
{
    public const LULUS = 'lulus';

    private const TINGKAT_BERIKUT = ['X' => 'XI', 'XI' => 'XII'];

    /**
     * Usulan nama kelas tujuan: "X RPL 1" → "XI RPL 1". Kelas XII → null (lulus).
     */
    public function usulanNama(Kelas $kelas): ?string
    {
        $berikut = self::TINGKAT_BERIKUT[$kelas->tingkat] ?? null;
        if (! $berikut) {
            return null;
        }

        $nama = preg_replace('/^'.preg_quote($kelas->tingkat, '/').'\b/', $berikut, $kelas->nama_kelas, 1, $diganti);

        return $diganti ? $nama : $berikut.' '.$kelas->nama_kelas;
    }

    /**
     * Buat kelas tujuan di tahun ajaran baru untuk setiap kelas X & XI sumber
     * (jika belum ada). Wali kelas tidak ikut disalin.
     */
    public function buatKelasTujuan(TahunAjaran $sumber, TahunAjaran $tujuan): int
    {
        $dibuat = 0;
        foreach (Kelas::where('tahun_ajaran_id', $sumber->id)->whereIn('tingkat', ['X', 'XI'])->get() as $kelas) {
            $baru = Kelas::firstOrCreate(
                ['nama_kelas' => $this->usulanNama($kelas), 'tahun_ajaran_id' => $tujuan->id],
                ['tingkat' => self::TINGKAT_BERIKUT[$kelas->tingkat], 'jurusan_id' => $kelas->jurusan_id]
            );
            $dibuat += (int) $baru->wasRecentlyCreated;
        }

        return $dibuat;
    }

    /**
     * Proses kenaikan kelas.
     *
     * @param  array<int|string, int|string|null>  $pemetaan  kelas_id sumber => kelas_id tujuan | 'lulus' | null (dilewati)
     * @return array{naik:int, lulus:int, dilewati:int}
     */
    public function proses(TahunAjaran $sumber, TahunAjaran $tujuan, array $pemetaan): array
    {
        return DB::transaction(function () use ($sumber, $tujuan, $pemetaan) {
            $hasil = ['naik' => 0, 'lulus' => 0, 'dilewati' => 0];

            $kelasSumber = Kelas::with('siswa')
                ->where('tahun_ajaran_id', $sumber->id)
                ->whereIn('id', array_keys($pemetaan))
                ->get();

            $kelasTujuanValid = Kelas::where('tahun_ajaran_id', $tujuan->id)->pluck('id')->all();

            foreach ($kelasSumber as $kelas) {
                $target = $pemetaan[$kelas->id] ?? null;
                /** @var Collection<int, Siswa> $daftarSiswa */
                $daftarSiswa = $kelas->siswa->where('status', 'aktif');

                if ($target === self::LULUS) {
                    $this->luluskan($daftarSiswa);
                    $hasil['lulus'] += $daftarSiswa->count();

                    continue;
                }

                if (! $target || ! in_array((int) $target, $kelasTujuanValid, true)) {
                    $hasil['dilewati'] += $daftarSiswa->count();

                    continue;
                }

                foreach ($daftarSiswa as $siswa) {
                    KelasSiswa::updateOrCreate(
                        ['siswa_id' => $siswa->id, 'tahun_ajaran_id' => $tujuan->id],
                        ['kelas_id' => (int) $target]
                    );
                    $hasil['naik']++;
                }
            }

            return $hasil;
        });
    }

    /** @param Collection<int, Siswa> $daftarSiswa */
    private function luluskan(Collection $daftarSiswa): void
    {
        if ($daftarSiswa->isEmpty()) {
            return;
        }

        Siswa::whereIn('id', $daftarSiswa->pluck('id'))->update(['status' => 'lulus']);
        User::whereIn('id', $daftarSiswa->pluck('user_id'))->update(['status' => 'nonaktif']);
    }
}
