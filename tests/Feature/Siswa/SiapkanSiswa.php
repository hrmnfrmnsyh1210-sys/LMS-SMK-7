<?php

namespace Tests\Feature\Siswa;

use App\Models\KelasSiswa;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Models\TahunAjaran;

trait SiapkanSiswa
{
    /**
     * Siswa yang terdaftar di kelas sebuah pengampu (tahun ajaran aktif), sudah login.
     *
     * @return array{0: Pengampu, 1: Siswa}
     */
    protected function siswaDiKelas(): array
    {
        TahunAjaran::aktif() ?? TahunAjaran::factory()->aktif()->create();

        $pengampu = Pengampu::factory()->create();
        $siswa = Siswa::factory()->create();

        KelasSiswa::create([
            'kelas_id' => $pengampu->kelas_id,
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => $pengampu->tahun_ajaran_id,
        ]);

        $this->actingAs($siswa->user);

        return [$pengampu, $siswa];
    }
}
