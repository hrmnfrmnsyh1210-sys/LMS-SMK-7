<?php

namespace Tests\Feature\Guru;

use App\Models\JawabanKuis;
use App\Models\Kuis;
use App\Models\Nilai;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NilaiTest extends TestCase
{
    use RefreshDatabase, SiapkanKelasAjar;

    public function test_total_bobot_harus_100(): void
    {
        [$pengampu] = $this->kelasAjar();

        $this->put(route('guru.nilai.bobot', $pengampu), ['bobot_tugas' => 30, 'bobot_kuis' => 30, 'bobot_uts' => 30, 'bobot_uas' => 30])
            ->assertSessionHasErrors(['bobot_tugas' => 'Total bobot harus 100%, saat ini 120%.']);

        $this->put(route('guru.nilai.bobot', $pengampu), ['bobot_tugas' => 25, 'bobot_kuis' => 25, 'bobot_uts' => 25, 'bobot_uas' => 25])
            ->assertSessionHasNoErrors();
        $this->assertSame(25, $pengampu->bobot()->bobot_uas);
    }

    public function test_nilai_akhir_dihitung_sesuai_bobot(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(1);
        $s = $siswa[0];

        // Tugas: 80/100, 50/50 (=100), satu tugas lewat deadline tidak dikumpulkan (=0) → rata 60
        $t1 = Tugas::factory()->create(['pengampu_id' => $pengampu->id]);
        $t2 = Tugas::factory()->create(['pengampu_id' => $pengampu->id, 'nilai_maks' => 50]);
        Tugas::factory()->lewatDeadline()->create(['pengampu_id' => $pengampu->id]);
        // Tugas belum lewat deadline & belum dikumpulkan tidak dihitung
        Tugas::factory()->create(['pengampu_id' => $pengampu->id]);
        PengumpulanTugas::factory()->create(['tugas_id' => $t1->id, 'siswa_id' => $s->id, 'nilai' => 80]);
        PengumpulanTugas::factory()->create(['tugas_id' => $t2->id, 'siswa_id' => $s->id, 'nilai' => 50]);

        // Kuis harian: 70
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        JawabanKuis::factory()->create(['kuis_id' => $kuis->id, 'siswa_id' => $s->id, 'total_skor' => 70]);

        // UTS 80, UAS 90 diinput manual; bobot default 20/20/30/30
        $this->put(route('guru.nilai.simpan', $pengampu), ['nilai' => [$s->id => ['nilai_uts' => 80, 'nilai_uas' => 90]]])
            ->assertSessionHasNoErrors();

        $nilai = Nilai::where('pengampu_id', $pengampu->id)->where('siswa_id', $s->id)->firstOrFail();
        $this->assertEquals(60, $nilai->nilai_tugas);
        $this->assertEquals(70, $nilai->nilai_kuis);
        // 60×20 + 70×20 + 80×30 + 90×30 = 7700 / 100
        $this->assertEquals(77, $nilai->nilai_akhir);
    }

    public function test_nilai_uts_uas_di_luar_rentang_ditolak(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(1);

        $this->put(route('guru.nilai.simpan', $pengampu), ['nilai' => [$siswa[0]->id => ['nilai_uts' => 120]]])
            ->assertSessionHasErrors('nilai.'.$siswa[0]->id.'.nilai_uts');
    }

    public function test_uts_uas_dapat_diambil_dari_kuis_berjenis_uts_dan_uas(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(2);
        $uts = Kuis::factory()->selesai()->create(['pengampu_id' => $pengampu->id, 'jenis' => 'uts']);
        JawabanKuis::factory()->create(['kuis_id' => $uts->id, 'siswa_id' => $siswa[0]->id, 'total_skor' => 88]);

        $this->post(route('guru.nilai.ambil-ujian', $pengampu))->assertSessionHas('success', 'Nilai UTS diambil dari hasil kuis.');

        $nilai = Nilai::where('pengampu_id', $pengampu->id)->get()->keyBy('siswa_id');
        $this->assertEquals(88, $nilai[$siswa[0]->id]->nilai_uts);
        // Tidak mengerjakan UTS yang sudah ditutup → 0
        $this->assertEquals(0, $nilai[$siswa[1]->id]->nilai_uts);
        $this->assertNull($nilai[$siswa[0]->id]->nilai_uas);
    }

    public function test_rekap_nilai_dapat_diekspor_ke_excel(): void
    {
        [$pengampu] = $this->kelasAjar();

        $this->get(route('guru.nilai.ekspor', $pengampu))->assertOk()->assertDownload();
    }
}
