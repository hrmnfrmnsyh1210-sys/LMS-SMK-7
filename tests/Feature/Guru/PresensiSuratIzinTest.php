<?php

namespace Tests\Feature\Guru;

use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\SuratIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensiSuratIzinTest extends TestCase
{
    use RefreshDatabase, SiapkanKelasAjar;

    public function test_form_presensi_default_hadir_kecuali_ada_surat_izin_disetujui(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(2);
        SuratIzin::factory()->create([
            'siswa_id' => $siswa[1]->id, 'kelas_id' => $pengampu->kelas_id, 'jenis' => 'sakit', 'status' => 'disetujui',
            'tanggal_mulai' => '2026-10-05', 'tanggal_selesai' => '2026-10-07',
        ]);

        $status = $this->get(route('guru.presensi.form', [$pengampu, 'tanggal' => '2026-10-06']))
            ->assertOk()
            ->viewData('status');

        $this->assertSame('hadir', $status[$siswa[0]->id]);
        $this->assertSame('sakit', $status[$siswa[1]->id]);
    }

    public function test_simpan_presensi_dan_rekap(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(2);

        $this->post(route('guru.presensi.store', $pengampu), [
            'pertemuan_ke' => 1, 'tanggal' => '2026-10-01',
            'status' => [$siswa[0]->id => 'hadir', $siswa[1]->id => 'alpa'],
        ])->assertRedirect(route('guru.presensi.index', $pengampu));

        // Ubah pertemuan yang sama tidak membuat duplikat
        $this->post(route('guru.presensi.store', $pengampu), [
            'pertemuan_ke' => 1, 'tanggal' => '2026-10-01',
            'status' => [$siswa[0]->id => 'izin', $siswa[1]->id => 'alpa'],
        ]);

        $this->assertSame(2, Presensi::count());
        $rekap = $this->get(route('guru.presensi.index', $pengampu))->assertOk()->viewData('rekap')->keyBy(fn ($r) => $r['siswa']->id);
        $this->assertSame(1, $rekap[$siswa[0]->id]['izin']);
        $this->assertSame(1, $rekap[$siswa[1]->id]['alpa']);
    }

    public function test_presensi_menolak_status_tidak_valid_dan_siswa_kelas_lain(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(1);
        $siswaLain = Siswa::factory()->create();

        $this->post(route('guru.presensi.store', $pengampu), [
            'pertemuan_ke' => 1, 'tanggal' => '2026-10-01', 'status' => [$siswa[0]->id => 'bolos'],
        ])->assertSessionHasErrors('status.'.$siswa[0]->id);

        $this->post(route('guru.presensi.store', $pengampu), [
            'pertemuan_ke' => 1, 'tanggal' => '2026-10-01', 'status' => [$siswaLain->id => 'hadir'],
        ]);
        $this->assertDatabaseMissing('presensi', ['siswa_id' => $siswaLain->id]);
    }

    public function test_wali_menyetujui_surat_izin_mengubah_presensi_yang_sudah_ada(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(1);
        $pengampu->kelas->update(['wali_kelas_id' => $pengampu->guru_id]);
        Presensi::create(['pengampu_id' => $pengampu->id, 'siswa_id' => $siswa[0]->id, 'tanggal' => '2026-10-02', 'pertemuan_ke' => 1, 'status' => 'alpa']);
        $surat = SuratIzin::factory()->create([
            'siswa_id' => $siswa[0]->id, 'kelas_id' => $pengampu->kelas_id, 'jenis' => 'izin',
            'tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2026-10-03',
        ]);

        $this->put(route('guru.wali.surat-izin.proses', $surat), ['status' => 'disetujui'])->assertSessionHas('success');

        $this->assertSame('disetujui', $surat->fresh()->status);
        $this->assertSame($pengampu->guru_id, $surat->fresh()->diproses_oleh);
        $this->assertSame('izin', Presensi::first()->status);
    }

    public function test_penolakan_surat_wajib_catatan_dan_tidak_mengubah_presensi(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(1);
        $pengampu->kelas->update(['wali_kelas_id' => $pengampu->guru_id]);
        Presensi::create(['pengampu_id' => $pengampu->id, 'siswa_id' => $siswa[0]->id, 'tanggal' => now()->toDateString(), 'pertemuan_ke' => 1, 'status' => 'alpa']);
        $surat = SuratIzin::factory()->create(['siswa_id' => $siswa[0]->id, 'kelas_id' => $pengampu->kelas_id]);

        $this->put(route('guru.wali.surat-izin.proses', $surat), ['status' => 'ditolak'])
            ->assertSessionHasErrors(['catatan' => 'Tuliskan alasan penolakan.']);

        $this->put(route('guru.wali.surat-izin.proses', $surat), ['status' => 'ditolak', 'catatan' => 'Tanpa bukti']);

        $this->assertSame('ditolak', $surat->fresh()->status);
        $this->assertSame('alpa', Presensi::first()->status);
    }

    public function test_guru_bukan_wali_kelas_surat_tidak_boleh_memproses(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(1);
        $surat = SuratIzin::factory()->create(['siswa_id' => $siswa[0]->id, 'kelas_id' => $pengampu->kelas_id]);

        $this->put(route('guru.wali.surat-izin.proses', $surat), ['status' => 'disetujui'])->assertForbidden();
        $this->assertSame('menunggu', $surat->fresh()->status);
    }
}
