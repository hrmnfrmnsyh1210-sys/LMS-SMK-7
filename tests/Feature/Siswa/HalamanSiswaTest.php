<?php

namespace Tests\Feature\Siswa;

use App\Models\ForumTopik;
use App\Models\JawabanKuis;
use App\Models\Kelas;
use App\Models\Kuis;
use App\Models\Materi;
use App\Models\Pengumuman;
use App\Models\Soal;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalamanSiswaTest extends TestCase
{
    use RefreshDatabase, SiapkanSiswa;

    public function test_semua_halaman_siswa_dapat_dibuka(): void
    {
        [$pengampu, $siswa] = $this->siswaDiKelas();
        $tugas = Tugas::factory()->create(['pengampu_id' => $pengampu->id]);
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        Materi::factory()->create(['pengampu_id' => $pengampu->id, 'judul' => 'Pengantar Basis Data']);
        $topik = ForumTopik::create(['pengampu_id' => $pengampu->id, 'user_id' => $siswa->user_id, 'judul' => 'Tanya ERD', 'isi' => 'Bagaimana?']);

        $halaman = [
            route('siswa.dashboard'),
            route('siswa.mapel.index'),
            route('siswa.mapel.show', $pengampu),
            route('siswa.tugas.index'),
            route('siswa.tugas.mapel', $pengampu),
            route('siswa.tugas.show', [$pengampu, $tugas]),
            route('siswa.kuis.index'),
            route('siswa.kuis.mapel', $pengampu),
            route('siswa.kuis.show', [$pengampu, $kuis]),
            route('siswa.forum.index', $pengampu),
            route('siswa.forum.show', [$pengampu, $topik]),
            route('siswa.jadwal'),
            route('siswa.presensi'),
            route('siswa.nilai'),
            route('siswa.pengumuman'),
            route('siswa.surat-izin.index'),
        ];

        foreach ($halaman as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('siswa.mapel.show', $pengampu))->assertSee('Pengantar Basis Data');
        $this->get(route('siswa.dashboard'))->assertSee($tugas->judul)->assertSee($kuis->judul);
    }

    public function test_halaman_kerjakan_kuis_dapat_dibuka_setelah_mulai(): void
    {
        [$pengampu] = $this->siswaDiKelas();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        Soal::factory()->create(['kuis_id' => $kuis->id, 'pertanyaan' => 'Ibukota Indonesia?']);
        Soal::factory()->esai()->create(['kuis_id' => $kuis->id]);

        $this->post(route('siswa.kuis.mulai', [$pengampu, $kuis]))->assertRedirect(route('siswa.kuis.kerjakan', [$pengampu, $kuis]));

        $this->get(route('siswa.kuis.kerjakan', [$pengampu, $kuis]))->assertOk()->assertSee('Ibukota Indonesia?');
        $this->assertSame(1, JawabanKuis::count());
    }

    public function test_pengumuman_yang_tampil_sesuai_target_siswa(): void
    {
        [$pengampu] = $this->siswaDiKelas();
        $pembuat = User::factory()->operator()->create();
        $buat = fn (string $judul, string $target, ?int $kelasId = null) => Pengumuman::create([
            'pembuat_user_id' => $pembuat->id, 'judul' => $judul, 'isi' => 'Isi', 'target' => $target, 'kelas_id' => $kelasId,
        ]);

        $buat('Untuk Semua', 'semua');
        $buat('Untuk Siswa', 'siswa');
        $buat('Untuk Kelasku', 'kelas', $pengampu->kelas_id);
        $buat('Khusus Guru', 'guru');
        $buat('Kelas Lain', 'kelas', Kelas::factory()->create()->id);

        $this->get(route('siswa.pengumuman'))
            ->assertSee('Untuk Semua')
            ->assertSee('Untuk Siswa')
            ->assertSee('Untuk Kelasku')
            ->assertDontSee('Khusus Guru')
            ->assertDontSee('Kelas Lain');
    }
}
