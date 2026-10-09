<?php

namespace Tests\Feature\Guru;

use App\Models\JawabanKuis;
use App\Models\Kuis;
use App\Models\Soal;
use App\Services\KuisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class KuisTest extends TestCase
{
    use RefreshDatabase, SiapkanKelasAjar;

    public function test_membuat_kuis_dengan_validasi_waktu(): void
    {
        [$pengampu] = $this->kelasAjar();
        $data = [
            'judul' => 'UTS Ganjil', 'jenis' => 'uts', 'durasi_menit' => 90, 'acak_soal' => '1',
            'waktu_mulai' => '2026-10-20 07:00', 'waktu_selesai' => '2026-10-20 06:00',
        ];

        $this->post(route('guru.kuis.store', $pengampu), $data)->assertSessionHasErrors('waktu_selesai');

        $this->post(route('guru.kuis.store', $pengampu), ['waktu_selesai' => '2026-10-20 12:00'] + $data)
            ->assertSessionHasNoErrors();

        $kuis = Kuis::firstOrFail();
        $this->assertSame('uts', $kuis->jenis);
        $this->assertTrue($kuis->acak_soal);
        $this->assertFalse($kuis->tampilkan_hasil);
    }

    public function test_soal_pg_wajib_opsi_dan_kunci_sedangkan_esai_tidak(): void
    {
        [$pengampu] = $this->kelasAjar();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);

        $this->post(route('guru.soal.store', [$pengampu, $kuis]), ['tipe' => 'pg', 'pertanyaan' => 'Apa?', 'bobot' => 1])
            ->assertSessionHasErrors(['opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'kunci_jawaban']);

        $this->post(route('guru.soal.store', [$pengampu, $kuis]), [
            'tipe' => 'esai', 'pertanyaan' => 'Jelaskan!', 'bobot' => 5, 'opsi_a' => 'diabaikan', 'kunci_jawaban' => 'a',
        ])->assertSessionHasNoErrors();

        $esai = Soal::firstOrFail();
        $this->assertSame('esai', $esai->tipe);
        $this->assertNull($esai->opsi_a);
        $this->assertNull($esai->kunci_jawaban);
    }

    public function test_import_soal_dari_excel(): void
    {
        [$pengampu] = $this->kelasAjar();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);

        $sheet = new Spreadsheet;
        $baris = [
            ['tipe', 'pertanyaan', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'kunci_jawaban', 'bobot'],
            ['pg', '1 + 1 = ...', '1', '2', '3', '4', 'B', '2'],
            ['esai', 'Jelaskan fotosintesis', '', '', '', '', '', '5'],
            ['pg', 'Tanpa kunci', 'a', 'b', 'c', 'd', '', '1'],
        ];
        foreach ($baris as $r => $kolom) {
            foreach ($kolom as $c => $nilai) {
                $sheet->getActiveSheet()->setCellValueExplicit([$c + 1, $r + 1], $nilai, DataType::TYPE_STRING);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'soal').'.xlsx';
        (new Xlsx($sheet))->save($path);

        $this->post(route('guru.soal.import', [$pengampu, $kuis]), ['file' => new UploadedFile($path, 'soal.xlsx', null, null, true)])
            ->assertSessionHas('success', '2 soal berhasil diimpor.')
            ->assertSessionHas('gagal_import', fn ($gagal) => $gagal[0]['baris'] === 4);

        $this->assertSame('b', $kuis->soal()->where('tipe', 'pg')->value('kunci_jawaban'));
        $this->assertSame(7, (int) $kuis->soal()->sum('bobot'));
    }

    public function test_soal_terkunci_setelah_ada_siswa_mengerjakan(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(1);
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        $soal = Soal::factory()->create(['kuis_id' => $kuis->id]);
        JawabanKuis::factory()->create(['kuis_id' => $kuis->id, 'siswa_id' => $siswa[0]->id]);

        $this->delete(route('guru.soal.destroy', [$pengampu, $kuis, $soal]))->assertSessionHas('error');
        $this->delete(route('guru.kuis.destroy', [$pengampu, $kuis]))->assertSessionHas('error');
        $this->assertModelExists($soal);
    }

    public function test_pg_dikoreksi_otomatis_dan_skor_final_setelah_esai_dikoreksi(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(1);
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        $pg1 = Soal::factory()->create(['kuis_id' => $kuis->id, 'kunci_jawaban' => 'a', 'bobot' => 1]);
        $pg2 = Soal::factory()->create(['kuis_id' => $kuis->id, 'kunci_jawaban' => 'b', 'bobot' => 1]);
        $esai = Soal::factory()->esai()->create(['kuis_id' => $kuis->id, 'bobot' => 2]);

        $jawaban = JawabanKuis::factory()->create(['kuis_id' => $kuis->id, 'siswa_id' => $siswa[0]->id]);
        $jawaban->detail()->createMany([
            ['soal_id' => $pg1->id, 'jawaban' => 'a'],
            ['soal_id' => $pg2->id, 'jawaban' => 'c'],
            ['soal_id' => $esai->id, 'jawaban' => 'Uraian siswa'],
        ]);

        app(KuisService::class)->koreksiOtomatis($jawaban);
        $this->assertNull($jawaban->fresh()->total_skor, 'Skor belum final sebelum esai dikoreksi');
        $this->assertTrue($jawaban->fresh()->menungguKoreksi());

        // Skor esai melebihi bobot dibatasi ke bobot
        $this->put(route('guru.kuis.koreksi.simpan', [$pengampu, $kuis, $jawaban]), ['skor' => [$esai->id => 1.5]])
            ->assertRedirect(route('guru.kuis.hasil', [$pengampu, $kuis]));

        // (1 + 0 + 1.5) / 4 × 100
        $this->assertEquals(62.5, $jawaban->fresh()->total_skor);

        $this->get(route('guru.kuis.hasil', [$pengampu, $kuis]))->assertOk()->assertSee('62.5');
    }

    public function test_skor_esai_dibatasi_maksimal_bobot(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(1);
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        $esai = Soal::factory()->esai()->create(['kuis_id' => $kuis->id, 'bobot' => 4]);
        $jawaban = JawabanKuis::factory()->create(['kuis_id' => $kuis->id, 'siswa_id' => $siswa[0]->id]);
        $jawaban->detail()->create(['soal_id' => $esai->id, 'jawaban' => 'Uraian']);

        $this->put(route('guru.kuis.koreksi.simpan', [$pengampu, $kuis, $jawaban]), ['skor' => [$esai->id => 10]]);

        $this->assertEquals(100, $jawaban->fresh()->total_skor);
    }
}
