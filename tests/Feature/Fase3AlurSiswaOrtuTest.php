<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\KelasSiswa;
use App\Models\Kuis;
use App\Models\OrangTua;
use App\Models\Pengampu;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\SuratIzin;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur lintas role: guru memberi tugas, kuis, dan presensi → siswa mengerjakan →
 * guru menilai → orang tua melihat hasilnya dan mengurus izin lewat wali kelas.
 */
class Fase3AlurSiswaOrtuTest extends TestCase
{
    use RefreshDatabase;

    public function test_alur_guru_siswa_dan_orang_tua(): void
    {
        TahunAjaran::factory()->aktif()->create();
        $pengampu = Pengampu::factory()->create();
        $guru = $pengampu->guru->user;
        $wali = Guru::factory()->create();
        $pengampu->kelas->update(['wali_kelas_id' => $wali->id]);

        $siswa = Siswa::factory()->create();
        KelasSiswa::create(['kelas_id' => $pengampu->kelas_id, 'siswa_id' => $siswa->id, 'tahun_ajaran_id' => $pengampu->tahun_ajaran_id]);
        $ortu = OrangTua::factory()->untuk($siswa)->create();

        // 1. Guru membuat tugas dan kuis PG (hasil ditampilkan).
        $this->actingAs($guru);
        $this->post(route('guru.tugas.store', $pengampu), [
            'judul' => 'Membuat ERD', 'deskripsi' => 'Rancang ERD perpustakaan.', 'deadline' => now()->addDays(2)->format('Y-m-d H:i'), 'nilai_maks' => 100,
        ])->assertSessionHasNoErrors();
        $this->post(route('guru.kuis.store', $pengampu), [
            'judul' => 'Kuis Basis Data', 'jenis' => 'kuis', 'durasi_menit' => 20, 'tampilkan_hasil' => '1',
            'waktu_mulai' => now()->subMinutes(5)->format('Y-m-d H:i'), 'waktu_selesai' => now()->addHour()->format('Y-m-d H:i'),
        ])->assertSessionHasNoErrors();
        $tugas = Tugas::sole();
        $kuis = Kuis::sole();
        foreach (['a', 'b'] as $kunci) {
            $this->post(route('guru.soal.store', [$pengampu, $kuis]), [
                'tipe' => 'pg', 'pertanyaan' => "Soal {$kunci}", 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'kunci_jawaban' => $kunci, 'bobot' => 1,
            ])->assertSessionHasNoErrors();
        }
        [$soal1, $soal2] = $kuis->soal()->orderBy('id')->get();

        // 2. Siswa melihat tugas & kuis di dashboard, mengumpulkan tugas, dan mengerjakan kuis (1 benar, 1 salah).
        $this->actingAs($siswa->user);
        $this->get(route('siswa.dashboard'))->assertSee('Membuat ERD')->assertSee('Kuis Basis Data');
        $this->post(route('siswa.tugas.kumpul', [$pengampu, $tugas]), ['jawaban_teks' => 'Entitas: Buku, Anggota, Peminjaman.'])
            ->assertSessionHas('success');
        $this->post(route('siswa.kuis.mulai', [$pengampu, $kuis]));
        $this->postJson(route('siswa.kuis.jawab', [$pengampu, $kuis]), ['soal_id' => $soal1->id, 'jawaban' => 'a'])->assertOk();
        $this->post(route('siswa.kuis.submit', [$pengampu, $kuis]), ['jawaban' => [$soal2->id => 'c']]);

        // 3. Guru menilai tugas, melihat skor kuis, dan mencatat siswa alpa.
        $this->actingAs($guru);
        $pengumpulan = $tugas->pengumpulan()->sole();
        $this->put(route('guru.tugas.nilai', [$pengampu, $tugas, $pengumpulan]), ['nilai' => 85, 'feedback' => 'Relasi sudah tepat.'])
            ->assertSessionHasNoErrors();
        $this->get(route('guru.kuis.hasil', [$pengampu, $kuis]))->assertSee('50');
        $this->post(route('guru.presensi.store', $pengampu), [
            'pertemuan_ke' => 1, 'tanggal' => now()->toDateString(), 'status' => [$siswa->id => 'alpa'],
        ])->assertSessionHasNoErrors();

        // 4. Orang tua mendapat notifikasi alpa, melihat nilai tugas & kuis, lalu mengajukan izin untuk hari itu.
        $this->actingAs($ortu->user);
        $this->assertStringContainsString('tercatat alpa', $ortu->user->notifications()->sole()->data['pesan']);
        $this->get(route('ortu.dashboard'))->assertSee('Membuat ERD')->assertSee('85')->assertSee('Kuis Basis Data');
        $this->get(route('ortu.kuis'))->assertSee('50');
        $this->post(route('ortu.surat-izin.store'), [
            'siswa_id' => $siswa->id, 'jenis' => 'sakit', 'tanggal_mulai' => now()->toDateString(), 'tanggal_selesai' => now()->toDateString(), 'keterangan' => 'Demam tinggi',
        ])->assertSessionHas('success');

        // 5. Wali kelas menyetujui → presensi alpa berubah menjadi sakit, siswa & orang tua diberi tahu.
        $this->actingAs($wali->user);
        $surat = SuratIzin::sole();
        $this->put(route('guru.wali.surat-izin.proses', $surat), ['status' => 'disetujui'])->assertSessionHasNoErrors();

        $this->assertSame('sakit', Presensi::sole()->status);
        $this->assertSame(2, $ortu->user->notifications()->count());
        $this->assertSame(1, $siswa->user->notifications()->where('data', 'like', '%disetujui%')->count());
    }
}
