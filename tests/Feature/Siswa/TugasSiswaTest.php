<?php

namespace Tests\Feature\Siswa;

use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TugasSiswaTest extends TestCase
{
    use RefreshDatabase, SiapkanSiswa;

    public function test_mengumpulkan_tugas_wajib_file_atau_jawaban_teks(): void
    {
        [$pengampu] = $this->siswaDiKelas();
        $tugas = Tugas::factory()->create(['pengampu_id' => $pengampu->id]);

        $this->post(route('siswa.tugas.kumpul', [$pengampu, $tugas]), [])
            ->assertSessionHasErrors(['jawaban_teks' => 'Unggah file atau tulis jawaban terlebih dahulu.']);

        $this->assertDatabaseCount('pengumpulan_tugas', 0);
    }

    public function test_mengumpulkan_tugas_dengan_file(): void
    {
        Storage::fake('public');
        [$pengampu, $siswa] = $this->siswaDiKelas();
        $tugas = Tugas::factory()->create(['pengampu_id' => $pengampu->id]);

        $this->post(route('siswa.tugas.kumpul', [$pengampu, $tugas]), ['file' => UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf')])
            ->assertRedirect(route('siswa.tugas.show', [$pengampu, $tugas]))
            ->assertSessionHas('success');

        $p = PengumpulanTugas::where('siswa_id', $siswa->id)->firstOrFail();
        Storage::disk('public')->assertExists($p->file_path);
        $this->assertFalse($p->terlambat);
    }

    public function test_kirim_ulang_mengganti_file_lama(): void
    {
        Storage::fake('public');
        [$pengampu, $siswa] = $this->siswaDiKelas();
        $tugas = Tugas::factory()->create(['pengampu_id' => $pengampu->id]);
        $lama = UploadedFile::fake()->create('lama.pdf', 10, 'application/pdf')->store("pengumpulan/{$tugas->id}", 'public');
        PengumpulanTugas::factory()->create(['tugas_id' => $tugas->id, 'siswa_id' => $siswa->id, 'file_path' => $lama]);

        // File lama sudah ada, jadi jawaban teks boleh kosong.
        $this->post(route('siswa.tugas.kumpul', [$pengampu, $tugas]), ['jawaban_teks' => ''])->assertSessionHasNoErrors();

        $this->post(route('siswa.tugas.kumpul', [$pengampu, $tugas]), ['file' => UploadedFile::fake()->create('baru.pdf', 10, 'application/pdf')]);

        Storage::disk('public')->assertMissing($lama);
        $this->assertSame(1, PengumpulanTugas::count());
    }

    public function test_pengumpulan_lewat_deadline_tetap_diterima_dan_ditandai_terlambat(): void
    {
        [$pengampu, $siswa] = $this->siswaDiKelas();
        $tugas = Tugas::factory()->lewatDeadline()->create(['pengampu_id' => $pengampu->id]);

        $this->post(route('siswa.tugas.kumpul', [$pengampu, $tugas]), ['jawaban_teks' => 'Maaf terlambat'])
            ->assertSessionHas('warning', 'Tugas terkirim, tetapi melewati deadline.');

        $this->assertTrue(PengumpulanTugas::where('siswa_id', $siswa->id)->firstOrFail()->terlambat);
    }

    public function test_tugas_yang_sudah_dinilai_tidak_dapat_diubah(): void
    {
        [$pengampu, $siswa] = $this->siswaDiKelas();
        $tugas = Tugas::factory()->create(['pengampu_id' => $pengampu->id]);
        PengumpulanTugas::factory()->create(['tugas_id' => $tugas->id, 'siswa_id' => $siswa->id, 'jawaban_teks' => 'Asli', 'nilai' => 90]);

        $this->post(route('siswa.tugas.kumpul', [$pengampu, $tugas]), ['jawaban_teks' => 'Diubah'])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('pengumpulan_tugas', ['siswa_id' => $siswa->id, 'jawaban_teks' => 'Asli']);
    }
}
