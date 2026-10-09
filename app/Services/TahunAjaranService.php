<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

class TahunAjaranService
{
    /**
     * Pergantian tahun ajaran/semester: aktifkan tahun ajaran tujuan dan
     * (opsional) salin kelas, anggota kelas, pengampu & jadwal dari sumber.
     * Dipakai saat berganti semester ganjil → genap pada tahun yang sama.
     *
     * @return array{kelas:int, siswa:int, pengampu:int, jadwal:int}
     */
    public function gantiTahunAjaran(TahunAjaran $tujuan, ?TahunAjaran $sumber, bool $salinData): array
    {
        return DB::transaction(function () use ($tujuan, $sumber, $salinData) {
            $hasil = ['kelas' => 0, 'siswa' => 0, 'pengampu' => 0, 'jadwal' => 0];

            if ($salinData && $sumber && $sumber->id !== $tujuan->id) {
                $hasil = $this->salinStruktur($sumber, $tujuan);
            }

            $tujuan->aktifkan();

            return $hasil;
        });
    }

    /** @return array{kelas:int, siswa:int, pengampu:int, jadwal:int} */
    public function salinStruktur(TahunAjaran $sumber, TahunAjaran $tujuan): array
    {
        $hasil = ['kelas' => 0, 'siswa' => 0, 'pengampu' => 0, 'jadwal' => 0];

        $kelasSumber = Kelas::with(['siswa', 'pengampu.jadwal'])
            ->where('tahun_ajaran_id', $sumber->id)
            ->get();

        foreach ($kelasSumber as $kelas) {
            $kelasBaru = Kelas::firstOrCreate(
                ['nama_kelas' => $kelas->nama_kelas, 'tahun_ajaran_id' => $tujuan->id],
                ['tingkat' => $kelas->tingkat, 'jurusan_id' => $kelas->jurusan_id, 'wali_kelas_id' => $kelas->wali_kelas_id]
            );
            $hasil['kelas'] += (int) $kelasBaru->wasRecentlyCreated;

            foreach ($kelas->siswa as $siswa) {
                $baru = KelasSiswa::firstOrCreate(
                    ['siswa_id' => $siswa->id, 'tahun_ajaran_id' => $tujuan->id],
                    ['kelas_id' => $kelasBaru->id]
                );
                $hasil['siswa'] += (int) $baru->wasRecentlyCreated;
            }

            foreach ($kelas->pengampu as $pengampu) {
                $pengampuBaru = $kelasBaru->pengampu()->firstOrCreate(
                    ['mapel_id' => $pengampu->mapel_id, 'tahun_ajaran_id' => $tujuan->id],
                    ['guru_id' => $pengampu->guru_id]
                );

                if (! $pengampuBaru->wasRecentlyCreated) {
                    continue;
                }

                $hasil['pengampu']++;
                foreach ($pengampu->jadwal as $jadwal) {
                    $pengampuBaru->jadwal()->create($jadwal->only(['hari', 'jam_mulai', 'jam_selesai', 'ruang']));
                    $hasil['jadwal']++;
                }
            }
        }

        return $hasil;
    }
}
