<?php

namespace Tests\Feature\Operator;

use App\Models\Berita;
use App\Models\Galeri;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Kepsek;
use App\Models\Mapel;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Smoke test: semua halaman operator terbuka tanpa error dengan data dummy. */
class HalamanOperatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_semua_halaman_operator_dapat_dibuka(): void
    {
        $this->seed(DummySeeder::class);
        $this->actingAs(User::where('username', 'operator')->first());

        $kelas = Kelas::first();
        $tahunLama = TahunAjaran::where('status_aktif', false)->first();

        $halaman = [
            route('operator.dashboard'),
            route('operator.guru.index'), route('operator.guru.index', ['q' => 'a', 'status' => 'aktif']),
            route('operator.guru.create'), route('operator.guru.edit', Guru::first()),
            route('operator.siswa.index'), route('operator.siswa.index', ['kelas_id' => $kelas->id, 'q' => '260']),
            route('operator.siswa.index', ['kelas_id' => 0]),
            route('operator.siswa.create'), route('operator.siswa.edit', Siswa::first()),
            route('operator.kepsek.index'), route('operator.kepsek.create'), route('operator.kepsek.edit', Kepsek::first()),
            route('operator.import.index'),
            route('operator.tahun-ajaran.index'), route('operator.tahun-ajaran.create'), route('operator.tahun-ajaran.edit', TahunAjaran::first()),
            route('operator.jurusan.index'), route('operator.jurusan.create'), route('operator.jurusan.edit', Jurusan::first()),
            route('operator.kelas.index'), route('operator.kelas.index', ['tingkat' => 'X']),
            route('operator.kelas.create'), route('operator.kelas.edit', $kelas), route('operator.kelas.siswa.index', $kelas),
            route('operator.mapel.index'), route('operator.mapel.create'), route('operator.mapel.edit', Mapel::first()),
            route('operator.pengampu.index'), route('operator.pengampu.index', ['kelas_id' => $kelas->id, 'q' => 'mat']),
            route('operator.pengampu.create'), route('operator.pengampu.edit', Pengampu::first()),
            route('operator.wali-kelas.index'),
            route('operator.jadwal.index'), route('operator.jadwal.index', ['mode' => 'guru']),
            route('operator.jadwal.create'), route('operator.jadwal.edit', Jadwal::first()),
            route('operator.pengaturan.edit'),
            route('operator.pergantian-tahun-ajaran.index'),
            route('operator.kenaikan-kelas.index'),
            route('operator.kenaikan-kelas.index', ['sumber_id' => TahunAjaran::aktifId(), 'tujuan_id' => $tahunLama->id]),
            route('operator.log.index'), route('operator.log.index', ['aksi' => 'login', 'dari' => now()->toDateString()]),
            route('operator.backup.index'),
            route('operator.website.edit'),
            route('operator.berita.index'), route('operator.berita.index', ['q' => 'a', 'kategori' => 'prestasi']),
            route('operator.berita.create'), route('operator.berita.edit', Berita::first()),
            route('operator.galeri.index'), route('operator.galeri.create'), route('operator.galeri.edit', Galeri::factory()->create()),
        ];

        foreach ($halaman as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_template_import_dapat_diunduh(): void
    {
        $this->actingAs($this->operator());

        foreach (['guru', 'siswa'] as $jenis) {
            $this->get(route('operator.import.template', $jenis))
                ->assertOk()
                ->assertDownload("template_import_{$jenis}.xlsx");
        }
    }
}
