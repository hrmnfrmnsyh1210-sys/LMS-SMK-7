<?php

namespace Tests\Feature\Siswa;

use App\Models\Guru;
use App\Models\SuratIzin;
use App\Notifications\Pemberitahuan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SuratIzinSiswaTest extends TestCase
{
    use RefreshDatabase, SiapkanSiswa;

    public function test_mengajukan_surat_izin_ke_wali_kelas(): void
    {
        [$pengampu, $siswa] = $this->siswaDiKelas();
        $wali = Guru::factory()->create();
        $pengampu->kelas->update(['wali_kelas_id' => $wali->id]);
        Notification::fake();

        $this->post(route('siswa.surat-izin.store'), [
            'jenis' => 'sakit',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
            'keterangan' => 'Demam',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('surat_izin', ['siswa_id' => $siswa->id, 'kelas_id' => $pengampu->kelas_id, 'status' => 'menunggu']);
        Notification::assertSentTo($wali->user, Pemberitahuan::class);
    }

    public function test_validasi_rentang_tanggal(): void
    {
        $this->siswaDiKelas();

        $this->post(route('siswa.surat-izin.store'), [
            'jenis' => 'izin',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->subDay()->toDateString(),
            'keterangan' => 'Acara keluarga',
        ])->assertSessionHasErrors('tanggal_selesai');

        $this->post(route('siswa.surat-izin.store'), [
            'jenis' => 'izin',
            'tanggal_mulai' => now()->subDays(10)->toDateString(),
            'tanggal_selesai' => now()->subDays(10)->toDateString(),
            'keterangan' => 'Acara keluarga',
        ])->assertSessionHasErrors(['tanggal_mulai' => 'Surat izin hanya dapat diajukan paling lama 7 hari setelah tanggal izin.']);
    }

    public function test_hanya_surat_milik_sendiri_yang_menunggu_dapat_dibatalkan(): void
    {
        [$pengampu, $siswa] = $this->siswaDiKelas();
        $menunggu = SuratIzin::factory()->create(['siswa_id' => $siswa->id, 'kelas_id' => $pengampu->kelas_id]);
        $disetujui = SuratIzin::factory()->create(['siswa_id' => $siswa->id, 'kelas_id' => $pengampu->kelas_id, 'status' => 'disetujui']);
        $milikLain = SuratIzin::factory()->create();

        $this->delete(route('siswa.surat-izin.destroy', $milikLain))->assertForbidden();
        $this->delete(route('siswa.surat-izin.destroy', $disetujui))->assertSessionHas('error');
        $this->delete(route('siswa.surat-izin.destroy', $menunggu))->assertSessionHas('success');

        $this->assertModelMissing($menunggu);
        $this->assertModelExists($disetujui);
        $this->assertModelExists($milikLain);
    }
}
