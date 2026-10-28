<?php

namespace Tests\Feature\Operator;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Pengampu;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->operator());
    }

    public function test_tahun_ajaran_pertama_otomatis_aktif_dan_hanya_satu_yang_aktif(): void
    {
        $this->post(route('operator.tahun-ajaran.store'), ['nama' => '2026/2027', 'semester' => 'ganjil'])->assertSessionHasNoErrors();
        $this->post(route('operator.tahun-ajaran.store'), ['nama' => '2026/2027', 'semester' => 'genap'])->assertSessionHasNoErrors();

        $ganjil = TahunAjaran::where('semester', 'ganjil')->first();
        $genap = TahunAjaran::where('semester', 'genap')->first();
        $this->assertTrue($ganjil->status_aktif);
        $this->assertFalse($genap->status_aktif);

        $this->post(route('operator.tahun-ajaran.aktifkan', $genap));

        $this->assertSame(1, TahunAjaran::where('status_aktif', true)->count());
        $this->assertTrue($genap->fresh()->status_aktif);
    }

    public function test_validasi_format_dan_duplikat_tahun_ajaran(): void
    {
        $this->post(route('operator.tahun-ajaran.store'), ['nama' => '2026-2027', 'semester' => 'ganjil'])
            ->assertSessionHasErrors(['nama' => 'Format tahun ajaran harus seperti 2026/2027.']);

        TahunAjaran::factory()->create(['nama' => '2026/2027', 'semester' => 'ganjil']);
        $this->post(route('operator.tahun-ajaran.store'), ['nama' => '2026/2027', 'semester' => 'ganjil'])
            ->assertSessionHasErrors('semester');
    }

    public function test_tahun_ajaran_aktif_atau_terpakai_tidak_bisa_dihapus(): void
    {
        $aktif = TahunAjaran::factory()->aktif()->create();
        $this->delete(route('operator.tahun-ajaran.destroy', $aktif))->assertSessionHas('error');

        $lama = TahunAjaran::factory()->create();
        Kelas::factory()->create(['tahun_ajaran_id' => $lama->id]);
        $this->delete(route('operator.tahun-ajaran.destroy', $lama))->assertSessionHas('error');
        $this->assertModelExists($lama);
    }

    public function test_crud_jurusan_dan_hapus_ditolak_jika_dipakai(): void
    {
        $this->post(route('operator.jurusan.store'), ['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak'])->assertSessionHasNoErrors();
        $jurusan = Jurusan::where('kode', 'RPL')->firstOrFail();

        $this->put(route('operator.jurusan.update', $jurusan), ['kode' => 'RPL', 'nama' => 'RPL Baru'])->assertSessionHasNoErrors();
        $this->assertSame('RPL Baru', $jurusan->fresh()->nama);

        Kelas::factory()->create(['jurusan_id' => $jurusan->id]);
        $this->delete(route('operator.jurusan.destroy', $jurusan))
            ->assertSessionHas('error', 'Jurusan RPL Baru tidak dapat dihapus karena masih dipakai oleh kelas.');
        $this->assertModelExists($jurusan);
    }

    public function test_hapus_mapel_yang_punya_pengampu_ditolak(): void
    {
        $pengampu = Pengampu::factory()->create();

        $this->delete(route('operator.mapel.destroy', $pengampu->mapel))->assertSessionHas('error');
        $this->assertModelExists($pengampu->mapel);

        $mapel = Mapel::factory()->create();
        $this->delete(route('operator.mapel.destroy', $mapel))->assertSessionHas('success');
        $this->assertModelMissing($mapel);
    }

    public function test_crud_kelas_di_tahun_ajaran_aktif(): void
    {
        $tahun = TahunAjaran::factory()->aktif()->create();
        $jurusan = Jurusan::factory()->create();

        $this->post(route('operator.kelas.store'), [
            'nama_kelas' => 'X RPL 1', 'tingkat' => 'X', 'jurusan_id' => $jurusan->id,
        ])->assertSessionHasNoErrors();

        $kelas = Kelas::where('nama_kelas', 'X RPL 1')->firstOrFail();
        $this->assertSame($tahun->id, $kelas->tahun_ajaran_id);

        // Nama kelas unik per tahun ajaran
        $this->post(route('operator.kelas.store'), ['nama_kelas' => 'X RPL 1', 'tingkat' => 'X', 'jurusan_id' => $jurusan->id])
            ->assertSessionHasErrors('nama_kelas');

        $this->delete(route('operator.kelas.destroy', $kelas))->assertSessionHas('success');
    }

    public function test_pengaturan_sekolah_dan_kebijakan_password(): void
    {
        $this->put(route('operator.pengaturan.update'), [
            'nama_sekolah' => 'SMK Uji Coba', 'min_panjang_password' => 10, 'kkm' => 70, 'toleransi_terlambat_guru' => 5,
        ])->assertSessionHasNoErrors();

        $this->assertSame('SMK Uji Coba', pengaturan('nama_sekolah'));
        $this->assertSame('70', pengaturan('kkm'));
        $this->assertSame('5', pengaturan('toleransi_terlambat_guru'));

        // Kebijakan password baru langsung berlaku
        $this->put(route('profil.password.update'), [
            'password_lama' => 'password', 'password' => 'sembilan9', 'password_confirmation' => 'sembilan9',
        ])->assertSessionHasErrors('password');

        $this->get(route('operator.dashboard'))->assertSee('SMK Uji Coba');
    }
}
