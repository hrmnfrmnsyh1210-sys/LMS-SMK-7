<?php

namespace Tests\Feature\Guru;

use App\Models\Guru;
use App\Models\Materi;
use App\Models\Pengampu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtorisasiGuruTest extends TestCase
{
    use RefreshDatabase, SiapkanKelasAjar;

    public function test_policy_hanya_guru_pemilik_yang_boleh_mengelola_pengampu(): void
    {
        $pengampu = Pengampu::factory()->create();

        $this->assertTrue($pengampu->guru->user->can('kelola', $pengampu));
        $this->assertFalse(Guru::factory()->create()->user->can('kelola', $pengampu));
        $this->assertFalse(User::factory()->operator()->create()->can('kelola', $pengampu));
        $this->assertFalse($this->userRole('siswa')->can('kelola', $pengampu));
    }

    public function test_guru_lain_ditolak_mengakses_kelas_ajar_bukan_miliknya(): void
    {
        [$pengampu] = $this->kelasAjar();
        $this->actingAs(Guru::factory()->create()->user);

        $this->get(route('guru.materi.index', $pengampu))->assertForbidden();
        $this->post(route('guru.tugas.store', $pengampu), [])->assertForbidden();
    }

    public function test_data_anak_milik_pengampu_lain_tidak_ditemukan_lewat_url_pengampu_sendiri(): void
    {
        [$pengampu] = $this->kelasAjar();
        $materiLain = Materi::factory()->create();

        $this->get(route('guru.materi.edit', [$pengampu, $materiLain]))->assertNotFound();
        $this->delete(route('guru.materi.destroy', [$pengampu, $materiLain]))->assertNotFound();
        $this->assertModelExists($materiLain);
    }

    public function test_siswa_tidak_dapat_membuka_halaman_guru(): void
    {
        $this->actingAs($this->userRole('siswa'))->get(route('guru.kelas.index'))->assertForbidden();
    }

    public function test_bukan_wali_kelas_ditolak_membuka_fitur_wali(): void
    {
        $this->kelasAjar();

        $this->get(route('guru.wali.rekap'))->assertForbidden();
        $this->get(route('guru.wali.surat-izin'))->assertForbidden();
    }
}
