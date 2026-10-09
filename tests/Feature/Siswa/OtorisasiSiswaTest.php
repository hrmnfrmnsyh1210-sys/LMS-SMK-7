<?php

namespace Tests\Feature\Siswa;

use App\Models\ForumTopik;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Models\User;
use App\Policies\PengampuPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtorisasiSiswaTest extends TestCase
{
    use RefreshDatabase, SiapkanSiswa;

    public function test_policy_ikuti_hanya_untuk_siswa_aktif_di_kelas_pengampu(): void
    {
        [$pengampu, $siswa] = $this->siswaDiKelas();
        $policy = new PengampuPolicy;

        $this->assertTrue($policy->ikuti($siswa->user, $pengampu));
        $this->assertFalse($policy->ikuti($siswa->user, Pengampu::factory()->create()), 'Mapel kelas lain');
        $this->assertFalse($policy->ikuti(Siswa::factory()->create()->user, $pengampu), 'Siswa kelas lain');
        $this->assertFalse($policy->ikuti($pengampu->guru->user, $pengampu), 'Guru bukan siswa');

        $siswa->update(['status' => 'lulus']);
        $this->assertFalse($policy->ikuti($siswa->user->fresh(), $pengampu), 'Siswa lulus');
    }

    public function test_siswa_tidak_dapat_membuka_mapel_kelas_lain(): void
    {
        $this->siswaDiKelas();
        $lain = Pengampu::factory()->create();

        $this->get(route('siswa.mapel.show', $lain))->assertForbidden();
    }

    public function test_tugas_milik_mapel_lain_tidak_ditemukan_lewat_mapel_sendiri(): void
    {
        [$pengampu] = $this->siswaDiKelas();
        $tugasLain = Tugas::factory()->create();

        $this->get(route('siswa.tugas.show', [$pengampu, $tugasLain]))->assertNotFound();
        $this->post(route('siswa.tugas.kumpul', [$pengampu, $tugasLain]), ['jawaban_teks' => 'x'])->assertNotFound();
    }

    public function test_role_lain_tidak_dapat_mengakses_halaman_siswa(): void
    {
        $this->actingAs(User::factory()->operator()->create())
            ->get(route('siswa.dashboard'))
            ->assertForbidden();
    }

    public function test_siswa_hanya_dapat_menghapus_postingan_forum_sendiri(): void
    {
        [$pengampu, $siswa] = $this->siswaDiKelas();
        $milikGuru = ForumTopik::create(['pengampu_id' => $pengampu->id, 'user_id' => $pengampu->guru->user_id, 'judul' => 'A', 'isi' => 'B']);
        $milikSendiri = ForumTopik::create(['pengampu_id' => $pengampu->id, 'user_id' => $siswa->user_id, 'judul' => 'C', 'isi' => 'D']);
        $balasanGuru = $milikSendiri->balasan()->create(['user_id' => $pengampu->guru->user_id, 'isi' => 'Jawab']);

        $this->delete(route('siswa.forum.destroy', [$pengampu, $milikGuru]))->assertForbidden();
        $this->delete(route('siswa.forum.balasan.destroy', [$pengampu, $milikSendiri, $balasanGuru]))->assertForbidden();
        $this->delete(route('siswa.forum.destroy', [$pengampu, $milikSendiri]))->assertRedirect(route('siswa.forum.index', $pengampu));

        $this->assertModelExists($milikGuru);
        $this->assertModelMissing($milikSendiri);
    }

    public function test_siswa_dapat_membuat_topik_dan_membalas(): void
    {
        [$pengampu, $siswa] = $this->siswaDiKelas();

        $this->post(route('siswa.forum.store', $pengampu), [])->assertSessionHasErrors(['judul', 'isi']);
        $this->post(route('siswa.forum.store', $pengampu), ['judul' => 'Tanya normalisasi', 'isi' => 'Apa itu 3NF?']);

        $topik = ForumTopik::firstOrFail();
        $this->post(route('siswa.forum.balas', [$pengampu, $topik]), ['isi' => 'Tambahan pertanyaan'])->assertSessionHas('success');

        $this->assertSame($siswa->user_id, $topik->user_id);
        $this->assertSame(1, $topik->balasan()->count());
    }
}
