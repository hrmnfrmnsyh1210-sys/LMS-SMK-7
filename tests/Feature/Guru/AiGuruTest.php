<?php

namespace Tests\Feature\Guru;

use App\Models\JawabanKuis;
use App\Models\JawabanKuisDetail;
use App\Models\Kuis;
use App\Models\Soal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiGuruTest extends TestCase
{
    use RefreshDatabase, SiapkanKelasAjar;

    private const URL_AI = 'https://ai.sumopod.com/v1/chat/completions';

    private function aktifkanAi(string $balasanAi): void
    {
        config(['services.ai.key' => 'kunci-uji', 'services.ai.base_url' => 'https://ai.sumopod.com/v1']);
        Http::preventStrayRequests();
        Http::fake([self::URL_AI => Http::response(['choices' => [['message' => ['content' => $balasanAi]]]])]);
    }

    public function test_draf_soal_ai_hanya_menyimpan_soal_yang_valid_ke_session(): void
    {
        [$pengampu] = $this->kelasAjar();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        $this->aktifkanAi("```json\n".json_encode(['soal' => [
            ['tipe' => 'pg', 'pertanyaan' => 'Fungsi echo?', 'opsi_a' => 'Cetak', 'opsi_b' => 'Hapus', 'opsi_c' => 'Ulang', 'opsi_d' => 'Simpan', 'kunci_jawaban' => 'A', 'bobot' => 1],
            ['tipe' => 'pg', 'pertanyaan' => 'Tanpa opsi', 'kunci_jawaban' => 'a'],
            ['tipe' => 'esai', 'pertanyaan' => 'Jelaskan perulangan!', 'bobot' => 5],
        ]])."\n```");

        $this->post(route('guru.soal.ai.buat', [$pengampu, $kuis]), ['topik' => 'PHP dasar', 'jumlah' => 3, 'tipe' => 'campuran', 'kesulitan' => 'sedang'])
            ->assertRedirect(route('guru.soal.ai.pratinjau', [$pengampu, $kuis]));

        $draf = session("soal_ai.{$kuis->id}");
        $this->assertCount(2, $draf);
        $this->assertSame('a', $draf[0]['kunci_jawaban']);
        $this->assertSame('esai', $draf[1]['tipe']);
        $this->assertSame(0, Soal::count());
        Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer kunci-uji') && str_contains($r['messages'][1]['content'], 'PHP dasar'));

        $this->get(route('guru.soal.ai.pratinjau', [$pengampu, $kuis]))->assertOk()->assertSee('Fungsi echo?');
    }

    public function test_menyimpan_draf_soal_ai_yang_dipilih(): void
    {
        [$pengampu] = $this->kelasAjar();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);

        $this->post(route('guru.soal.ai.simpan', [$pengampu, $kuis]), ['soal' => [['tipe' => 'pg', 'pertanyaan' => 'Apa?', 'bobot' => 1]]])
            ->assertSessionHasErrors(['soal.0.opsi_a', 'soal.0.kunci_jawaban']);

        $this->post(route('guru.soal.ai.simpan', [$pengampu, $kuis]), ['soal' => [
            ['tipe' => 'esai', 'pertanyaan' => 'Jelaskan!', 'bobot' => 4, 'opsi_a' => 'diabaikan'],
        ]])->assertRedirect(route('guru.kuis.show', [$pengampu, $kuis]));

        $soal = $kuis->soal()->sole();
        $this->assertSame('esai', $soal->tipe);
        $this->assertNull($soal->opsi_a);
    }

    public function test_gagal_ai_dikembalikan_sebagai_pesan_error(): void
    {
        [$pengampu] = $this->kelasAjar();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        config(['services.ai.key' => 'kunci-uji']);
        Http::preventStrayRequests();
        Http::fake([self::URL_AI => Http::response(['error' => 'quota'], 429)]);

        $this->post(route('guru.soal.ai.buat', [$pengampu, $kuis]), ['topik' => 'PHP', 'jumlah' => 2, 'tipe' => 'pg', 'kesulitan' => 'mudah'])
            ->assertSessionHas('error', 'Kuota atau batas permintaan AI sedang habis. Coba lagi nanti.');
    }

    public function test_draf_soal_ditolak_bila_kuis_sudah_dikerjakan(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        JawabanKuis::factory()->create(['kuis_id' => $kuis->id, 'siswa_id' => $siswa[0]->id]);
        Http::preventStrayRequests();

        $this->post(route('guru.soal.ai.buat', [$pengampu, $kuis]), ['topik' => 'PHP', 'jumlah' => 2, 'tipe' => 'pg', 'kesulitan' => 'mudah'])
            ->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_saran_koreksi_esai_dibatasi_bobot_dan_tidak_mengubah_skor(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        $esai = Soal::factory()->esai()->create(['kuis_id' => $kuis->id, 'bobot' => 5]);
        $jawaban = JawabanKuis::factory()->create(['kuis_id' => $kuis->id, 'siswa_id' => $siswa[0]->id]);
        JawabanKuisDetail::create(['jawaban_kuis_id' => $jawaban->id, 'soal_id' => $esai->id, 'jawaban' => 'Perulangan mengulang perintah.']);
        $this->aktifkanAi(json_encode(['hasil' => [
            ['soal_id' => $esai->id, 'skor' => 9, 'alasan' => 'Lengkap.'],
            ['soal_id' => 99999, 'skor' => 3, 'alasan' => 'Soal lain.'],
        ]]));

        $this->postJson(route('guru.kuis.koreksi.ai', [$pengampu, $kuis, $jawaban]))
            ->assertOk()
            ->assertExactJson(['saran' => [$esai->id => ['skor' => 5, 'alasan' => 'Lengkap.']]]);

        $this->assertNull($jawaban->detail()->sole()->skor);
    }

    public function test_fitur_ai_nonaktif_tanpa_api_key(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        $esai = Soal::factory()->esai()->create(['kuis_id' => $kuis->id]);
        config(['services.ai.key' => null]);
        Http::preventStrayRequests();

        $this->get(route('guru.kuis.show', [$pengampu, $kuis]))->assertOk()->assertSee('Fitur AI belum diaktifkan');

        $jawaban = JawabanKuis::factory()->create(['kuis_id' => $kuis->id, 'siswa_id' => $siswa[0]->id]);
        JawabanKuisDetail::create(['jawaban_kuis_id' => $jawaban->id, 'soal_id' => $esai->id, 'jawaban' => 'Isi']);
        $this->postJson(route('guru.kuis.koreksi.ai', [$pengampu, $kuis, $jawaban]))->assertStatus(503);
    }

    public function test_guru_lain_tidak_dapat_meminta_saran_koreksi(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar();
        $kuis = Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        $jawaban = JawabanKuis::factory()->create(['kuis_id' => $kuis->id, 'siswa_id' => $siswa[0]->id]);
        Http::preventStrayRequests();
        $this->kelasAjar(); // login sebagai guru lain

        $this->postJson(route('guru.kuis.koreksi.ai', [$pengampu, $kuis, $jawaban]))->assertForbidden();
        Http::assertNothingSent();
    }
}
