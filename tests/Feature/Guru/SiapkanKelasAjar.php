<?php

namespace Tests\Feature\Guru;

use App\Models\KelasSiswa;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;

trait SiapkanKelasAjar
{
    /**
     * Pengampu milik guru baru beserta sejumlah siswa di kelasnya.
     *
     * @return array{0: Pengampu, 1: Collection<int, Siswa>}
     */
    protected function kelasAjar(int $jumlahSiswa = 3): array
    {
        TahunAjaran::aktif() ?? TahunAjaran::factory()->aktif()->create();

        $pengampu = Pengampu::factory()->create();
        $siswa = Siswa::factory()->count($jumlahSiswa)->create()->sortBy('nama')->values();

        foreach ($siswa as $s) {
            KelasSiswa::create([
                'kelas_id' => $pengampu->kelas_id,
                'siswa_id' => $s->id,
                'tahun_ajaran_id' => $pengampu->tahun_ajaran_id,
            ]);
        }

        $this->actingAs($pengampu->guru->user);

        return [$pengampu, $siswa];
    }
}
