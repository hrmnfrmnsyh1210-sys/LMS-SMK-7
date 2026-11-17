<?php

namespace Tests\Feature\Guru;

use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\SuratIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_pertemuan_otomatis_dan_jurnal_materi_tugas_tersimpan(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(2);

        // Kunjungan pertama tanpa parameter pertemuan otomatis mengarah ke pertemuan 1
        $this->get(route('guru.presensi.form', $pengampu))
            ->assertOk()
            ->assertSee('Pertemuan ke-1')
            ->assertViewHas('pertemuan', 1);

        // Guru mengisi presensi beserta materi dan tugas hari ini
        $this->post(route('guru.presensi.store', $pengampu), [
            'pertemuan_ke' => 1,
            'tanggal' => today()->toDateString(),
            'status' => [$siswa[0]->id => 'hadir', $siswa[1]->id => 'hadir'],
            'materi' => 'Pengenalan Algoritma Pemrograman',
            'tugas' => 'Mengerjakan flowchart studi kasus 1',
            'catatan' => 'Siswa sangat antusias',
        ])->assertRedirect(route('guru.presensi.index', $pengampu));

        $this->assertDatabaseHas('presensi', [
            'pengampu_id' => $pengampu->id,
            'pertemuan_ke' => 1,
            'materi' => 'Pengenalan Algoritma Pemrograman',
            'tugas' => 'Mengerjakan flowchart studi kasus 1',
            'catatan' => 'Siswa sangat antusias',
        ]);

        // Kunjungan berikutnya di hari yang sama otomatis memuat pertemuan 1 yang sudah ada
        $this->get(route('guru.presensi.form', $pengampu))
            ->assertOk()
            ->assertViewHas('pertemuan', 1)
            ->assertSee('Pengenalan Algoritma Pemrograman')
            ->assertSee('Mengerjakan flowchart studi kasus 1');
    }

    public function test_guru_dapat_mengunggah_dan_menghapus_dokumentasi_bukti_jurnal(): void
    {
        Storage::fake('public');
        [$pengampu, $siswa] = $this->kelasAjar(2);

        $file = UploadedFile::fake()->image('bukti_pembelajaran.jpg');

        $this->post(route('guru.presensi.store', $pengampu), [
            'pertemuan_ke' => 1,
            'tanggal' => today()->toDateString(),
            'status' => [$siswa[0]->id => 'hadir', $siswa[1]->id => 'hadir'],
            'materi' => 'Praktikum Jaringan Dasar',
            'tugas' => 'Konfigurasi IP Address di Packet Tracer',
            'dokumentasi' => $file,
        ])->assertRedirect(route('guru.presensi.index', $pengampu));

        $presensi = Presensi::where('pengampu_id', $pengampu->id)->where('pertemuan_ke', 1)->first();
        $this->assertNotNull($presensi->dokumentasi);
        Storage::disk('public')->assertExists($presensi->dokumentasi);

        // Form memuat bukti yang sudah diunggah
        $this->get(route('guru.presensi.form', $pengampu))
            ->assertOk()
            ->assertViewHas('dokumentasi', $presensi->dokumentasi);

        // Menghapus bukti via checkbox hapus_dokumentasi
        $this->post(route('guru.presensi.store', $pengampu), [
            'pertemuan_ke' => 1,
            'tanggal' => today()->toDateString(),
            'status' => [$siswa[0]->id => 'hadir', $siswa[1]->id => 'hadir'],
            'materi' => 'Praktikum Jaringan Dasar',
            'hapus_dokumentasi' => '1',
        ])->assertRedirect(route('guru.presensi.index', $pengampu));

        $presensi->refresh();
        $this->assertNull($presensi->dokumentasi);
    }
}
