<?php

namespace Tests\Feature\Guru;

use App\Models\Materi;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use App\Notifications\Pemberitahuan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MateriTugasTest extends TestCase
{
    use RefreshDatabase, SiapkanKelasAjar;

    public function test_guru_mengunggah_materi_dan_siswa_mendapat_notifikasi(): void
    {
        Storage::fake('public');
        Notification::fake();
        [$pengampu, $siswa] = $this->kelasAjar();

        $this->post(route('guru.materi.store', $pengampu), [
            'judul' => 'Pengenalan HTML',
            'pertemuan_ke' => 1,
            'file' => UploadedFile::fake()->create('html.pdf', 200, 'application/pdf'),
            'tautan' => 'https://www.youtube.com/watch?v=abc',
        ])->assertRedirect(route('guru.materi.index', $pengampu));

        $materi = Materi::firstOrFail();
        $this->assertSame($pengampu->id, $materi->pengampu_id);
        Storage::disk('public')->assertExists($materi->file_path);
        Notification::assertSentTo($siswa->first()->user, Pemberitahuan::class);

        $this->get(route('guru.materi.index', $pengampu))->assertOk()->assertSee('Pengenalan HTML');
    }

    public function test_materi_wajib_berisi_file_tautan_atau_deskripsi(): void
    {
        [$pengampu] = $this->kelasAjar();

        $this->post(route('guru.materi.store', $pengampu), ['judul' => 'Kosong', 'pertemuan_ke' => 1])
            ->assertSessionHasErrors(['file' => 'Isi minimal salah satu: file, tautan, atau deskripsi materi.']);
        $this->assertDatabaseCount('materi', 0);
    }

    public function test_file_materi_berbahaya_ditolak(): void
    {
        [$pengampu] = $this->kelasAjar();

        $this->post(route('guru.materi.store', $pengampu), [
            'judul' => 'X', 'pertemuan_ke' => 1, 'file' => UploadedFile::fake()->create('shell.php', 1, 'application/x-php'),
        ])->assertSessionHasErrors('file');
    }

    public function test_daftar_pengumpulan_membedakan_tepat_waktu_terlambat_dan_belum(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(3);
        $tugas = Tugas::factory()->create(['pengampu_id' => $pengampu->id, 'deadline' => now()->subHour()]);

        PengumpulanTugas::factory()->create(['tugas_id' => $tugas->id, 'siswa_id' => $siswa[0]->id, 'waktu_kumpul' => now()->subHours(2)]);
        PengumpulanTugas::factory()->create(['tugas_id' => $tugas->id, 'siswa_id' => $siswa[1]->id, 'waktu_kumpul' => now()]);

        $respon = $this->get(route('guru.tugas.show', [$pengampu, $tugas]))->assertOk();
        $daftar = $respon->viewData('daftar')->keyBy(fn ($b) => $b['siswa']->id);

        $this->assertSame('sudah', $daftar[$siswa[0]->id]['status']);
        $this->assertSame('terlambat', $daftar[$siswa[1]->id]['status']);
        $this->assertSame('belum', $daftar[$siswa[2]->id]['status']);
    }

    public function test_guru_memberi_nilai_dan_feedback_pengumpulan(): void
    {
        Notification::fake();
        [$pengampu, $siswa] = $this->kelasAjar(1);
        $tugas = Tugas::factory()->create(['pengampu_id' => $pengampu->id, 'nilai_maks' => 50]);
        $p = PengumpulanTugas::factory()->create(['tugas_id' => $tugas->id, 'siswa_id' => $siswa[0]->id]);

        $this->put(route('guru.tugas.nilai', [$pengampu, $tugas, $p]), ['nilai' => 60])->assertSessionHasErrors('nilai');

        $this->put(route('guru.tugas.nilai', [$pengampu, $tugas, $p]), ['nilai' => 45, 'feedback' => 'Bagus'])
            ->assertSessionHas('success');

        $this->assertEquals(45, $p->fresh()->nilai);
        $this->assertSame('Bagus', $p->fresh()->feedback);
        Notification::assertSentTo($siswa[0]->user, Pemberitahuan::class);
    }

    public function test_tugas_yang_sudah_dikumpulkan_tidak_bisa_dihapus(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(1);
        $tugas = Tugas::factory()->create(['pengampu_id' => $pengampu->id]);
        PengumpulanTugas::factory()->create(['tugas_id' => $tugas->id, 'siswa_id' => $siswa[0]->id]);

        $this->delete(route('guru.tugas.destroy', [$pengampu, $tugas]))->assertSessionHas('error');
        $this->assertModelExists($tugas);
    }
}
