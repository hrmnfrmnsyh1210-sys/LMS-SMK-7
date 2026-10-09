<?php

namespace Tests\Feature\Siswa;

use App\Models\JawabanKuis;
use App\Models\Kuis;
use App\Models\Soal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KuisSiswaTest extends TestCase
{
    use RefreshDatabase, SiapkanSiswa;

    public function test_kuis_yang_belum_dibuka_tidak_dapat_dimulai(): void
    {
        [$pengampu] = $this->siswaDiKelas();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id, 'waktu_mulai' => now()->addDay(), 'waktu_selesai' => now()->addDays(2)]);
        Soal::factory()->create(['kuis_id' => $kuis->id]);

        $this->post(route('siswa.kuis.mulai', [$pengampu, $kuis]))->assertSessionHas('error', 'Kuis ini sedang tidak dibuka.');

        $this->assertDatabaseCount('jawaban_kuis', 0);
    }

    public function test_mulai_menyimpan_urutan_soal_dan_tidak_membuat_ulang(): void
    {
        [$pengampu] = $this->siswaDiKelas();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id, 'acak_soal' => true]);
        $soalIds = Soal::factory()->count(5)->create(['kuis_id' => $kuis->id])->pluck('id')->sort()->values()->all();

        $this->post(route('siswa.kuis.mulai', [$pengampu, $kuis]));
        $this->post(route('siswa.kuis.mulai', [$pengampu, $kuis]));

        $jawaban = JawabanKuis::sole();
        $this->assertEqualsCanonicalizing($soalIds, $jawaban->urutan_soal);
    }

    public function test_autosave_jawaban_dan_submit_menghasilkan_skor(): void
    {
        [$pengampu] = $this->siswaDiKelas();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        $benar = Soal::factory()->create(['kuis_id' => $kuis->id, 'kunci_jawaban' => 'a']);
        $salah = Soal::factory()->create(['kuis_id' => $kuis->id, 'kunci_jawaban' => 'b']);
        $this->post(route('siswa.kuis.mulai', [$pengampu, $kuis]));

        $this->postJson(route('siswa.kuis.jawab', [$pengampu, $kuis]), ['soal_id' => $benar->id, 'jawaban' => 'a'])
            ->assertOk()->assertJson(['tersimpan' => true]);

        $this->post(route('siswa.kuis.submit', [$pengampu, $kuis]), ['jawaban' => [$salah->id => 'c']])
            ->assertRedirect(route('siswa.kuis.show', [$pengampu, $kuis]));

        $jawaban = JawabanKuis::sole();
        $this->assertNotNull($jawaban->waktu_submit);
        $this->assertEquals(50, $jawaban->total_skor);
        $this->assertSame('c', $jawaban->detail()->where('soal_id', $salah->id)->value('jawaban'));
    }

    public function test_autosave_menolak_soal_dari_kuis_lain(): void
    {
        [$pengampu] = $this->siswaDiKelas();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        Soal::factory()->create(['kuis_id' => $kuis->id]);
        $soalLain = Soal::factory()->create();
        $this->post(route('siswa.kuis.mulai', [$pengampu, $kuis]));

        $this->postJson(route('siswa.kuis.jawab', [$pengampu, $kuis]), ['soal_id' => $soalLain->id, 'jawaban' => 'a'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('soal_id');
    }

    public function test_jawaban_ditolak_setelah_waktu_habis_dan_kuis_dikirim_otomatis(): void
    {
        [$pengampu] = $this->siswaDiKelas();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id, 'durasi_menit' => 10]);
        $soal = Soal::factory()->create(['kuis_id' => $kuis->id, 'kunci_jawaban' => 'a']);
        $this->post(route('siswa.kuis.mulai', [$pengampu, $kuis]));
        $this->postJson(route('siswa.kuis.jawab', [$pengampu, $kuis]), ['soal_id' => $soal->id, 'jawaban' => 'a']);

        $this->travel(11)->minutes();

        $this->postJson(route('siswa.kuis.jawab', [$pengampu, $kuis]), ['soal_id' => $soal->id, 'jawaban' => 'b'])
            ->assertUnprocessable()
            ->assertJson(['pesan' => 'Waktu pengerjaan sudah berakhir.']);

        $this->get(route('siswa.kuis.kerjakan', [$pengampu, $kuis]))
            ->assertRedirect(route('siswa.kuis.show', [$pengampu, $kuis]));

        $jawaban = JawabanKuis::sole();
        $this->assertNotNull($jawaban->waktu_submit);
        $this->assertEquals(100, $jawaban->total_skor, 'Jawaban yang tersimpan sebelum waktu habis tetap dinilai');
    }

    public function test_jawaban_pada_submit_diabaikan_bila_waktu_sudah_habis(): void
    {
        [$pengampu] = $this->siswaDiKelas();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id, 'durasi_menit' => 5]);
        $soal = Soal::factory()->create(['kuis_id' => $kuis->id, 'kunci_jawaban' => 'a']);
        $this->post(route('siswa.kuis.mulai', [$pengampu, $kuis]));

        $this->travel(10)->minutes();
        $this->post(route('siswa.kuis.submit', [$pengampu, $kuis]), ['jawaban' => [$soal->id => 'a']]);

        $this->assertEquals(0, JawabanKuis::sole()->total_skor);
    }

    public function test_pindah_tab_tercatat(): void
    {
        [$pengampu] = $this->siswaDiKelas();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        Soal::factory()->create(['kuis_id' => $kuis->id]);
        $this->post(route('siswa.kuis.mulai', [$pengampu, $kuis]));

        $this->postJson(route('siswa.kuis.pindah-tab', [$pengampu, $kuis]))->assertOk();
        $this->postJson(route('siswa.kuis.pindah-tab', [$pengampu, $kuis]))->assertJson(['jumlah' => 2]);
    }

    public function test_pembahasan_baru_tampil_setelah_kuis_ditutup(): void
    {
        [$pengampu] = $this->siswaDiKelas();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id, 'waktu_selesai' => now()->addHour()]);
        Soal::factory()->create(['kuis_id' => $kuis->id, 'kunci_jawaban' => 'a']);
        $this->post(route('siswa.kuis.mulai', [$pengampu, $kuis]));
        $this->post(route('siswa.kuis.submit', [$pengampu, $kuis]));

        $this->get(route('siswa.kuis.show', [$pengampu, $kuis]))->assertDontSee('Pembahasan</h3>', false);

        $this->travel(2)->hours();
        $this->get(route('siswa.kuis.show', [$pengampu, $kuis]))->assertSee('Pembahasan</h3>', false);
    }
}
