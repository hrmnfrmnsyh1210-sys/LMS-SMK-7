<?php

namespace Tests\Feature;

use App\Models\JawabanKuis;
use App\Models\Kuis;
use App\Models\Nilai;
use App\Models\PengumpulanTugas;
use App\Models\Presensi;
use App\Models\Tugas;
use App\Services\KuisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Guru\SiapkanKelasAjar;
use Tests\TestCase;

/**
 * Kriteria selesai Fase 2: guru membuat materi, tugas, kuis PG + esai,
 * mengisi presensi, dan menghasilkan nilai akhir yang benar sesuai bobot.
 */
class Fase2AlurGuruTest extends TestCase
{
    use RefreshDatabase, SiapkanKelasAjar;

    public function test_alur_guru_dari_materi_hingga_nilai_akhir(): void
    {
        Storage::fake('public');
        [$pengampu, $siswa] = $this->kelasAjar(1);
        $s = $siswa[0];

        // Materi
        $this->post(route('guru.materi.store', $pengampu), [
            'judul' => 'Algoritma Dasar', 'pertemuan_ke' => 1,
            'file' => UploadedFile::fake()->create('algoritma.pptx', 300),
        ])->assertSessionHasNoErrors();

        // Tugas, siswa mengumpulkan, guru menilai 80
        $this->post(route('guru.tugas.store', $pengampu), [
            'judul' => 'Flowchart', 'deskripsi' => 'Buat flowchart.', 'deadline' => now()->addDays(3)->format('Y-m-d H:i'), 'nilai_maks' => 100,
        ])->assertSessionHasNoErrors();
        $tugas = Tugas::firstOrFail();
        $pengumpulan = PengumpulanTugas::factory()->create(['tugas_id' => $tugas->id, 'siswa_id' => $s->id]);
        $this->put(route('guru.tugas.nilai', [$pengampu, $tugas, $pengumpulan]), ['nilai' => 80])->assertSessionHasNoErrors();

        // Kuis PG + esai
        $this->post(route('guru.kuis.store', $pengampu), [
            'judul' => 'Kuis 1', 'jenis' => 'kuis', 'durasi_menit' => 30,
            'waktu_mulai' => now()->subHour()->format('Y-m-d H:i'), 'waktu_selesai' => now()->addHour()->format('Y-m-d H:i'),
        ])->assertSessionHasNoErrors();
        $kuis = Kuis::firstOrFail();
        $this->post(route('guru.soal.store', [$pengampu, $kuis]), [
            'tipe' => 'pg', 'pertanyaan' => '2 × 3 = ?', 'opsi_a' => '5', 'opsi_b' => '6', 'opsi_c' => '7', 'opsi_d' => '8', 'kunci_jawaban' => 'b', 'bobot' => 2,
        ])->assertSessionHasNoErrors();
        $this->post(route('guru.soal.store', [$pengampu, $kuis]), ['tipe' => 'esai', 'pertanyaan' => 'Jelaskan perkalian.', 'bobot' => 2])
            ->assertSessionHasNoErrors();
        [$pg, $esai] = $kuis->soal()->orderBy('id')->get();

        // Siswa menjawab (fitur siswa di fase 3): PG benar, esai dikoreksi guru 1 dari 2
        $jawaban = JawabanKuis::factory()->create(['kuis_id' => $kuis->id, 'siswa_id' => $s->id]);
        $jawaban->detail()->createMany([['soal_id' => $pg->id, 'jawaban' => 'b'], ['soal_id' => $esai->id, 'jawaban' => 'Penjumlahan berulang.']]);
        app(KuisService::class)->koreksiOtomatis($jawaban);
        $this->put(route('guru.kuis.koreksi.simpan', [$pengampu, $kuis, $jawaban]), ['skor' => [$esai->id => 1]])->assertSessionHasNoErrors();
        $this->assertEquals(75, $jawaban->fresh()->total_skor);

        // Presensi
        $this->post(route('guru.presensi.store', $pengampu), [
            'pertemuan_ke' => 1, 'tanggal' => now()->toDateString(), 'status' => [$s->id => 'hadir'],
        ])->assertSessionHasNoErrors();
        $this->assertSame('hadir', Presensi::firstOrFail()->status);

        // Bobot 25/25/25/25, UTS 70, UAS 90 → (80 + 75 + 70 + 90) / 4 = 78.75
        $this->put(route('guru.nilai.bobot', $pengampu), ['bobot_tugas' => 25, 'bobot_kuis' => 25, 'bobot_uts' => 25, 'bobot_uas' => 25])
            ->assertSessionHasNoErrors();
        $this->put(route('guru.nilai.simpan', $pengampu), ['nilai' => [$s->id => ['nilai_uts' => 70, 'nilai_uas' => 90]]])
            ->assertSessionHasNoErrors();

        $nilai = Nilai::where('pengampu_id', $pengampu->id)->where('siswa_id', $s->id)->firstOrFail();
        $this->assertEquals(80, $nilai->nilai_tugas);
        $this->assertEquals(75, $nilai->nilai_kuis);
        $this->assertEquals(78.75, $nilai->nilai_akhir);

        $this->get(route('guru.nilai.index', $pengampu))->assertOk()->assertSee('78.75');
    }
}
