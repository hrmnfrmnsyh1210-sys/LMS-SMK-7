<?php

namespace Tests\Feature\Guru;

use App\Models\Guru;
use Database\Seeders\DummyPembelajaranSeeder;
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Smoke test: semua halaman guru terbuka tanpa error dengan data dummy lengkap. */
class HalamanGuruTest extends TestCase
{
    use RefreshDatabase;

    public function test_semua_halaman_guru_wali_kelas_dapat_dibuka(): void
    {
        $this->seed([DummySeeder::class, DummyPembelajaranSeeder::class]);

        // Guru kedua adalah wali kelas X RPL 1 pada data dummy.
        $guru = Guru::where('nip', '198001012010011002')->firstOrFail();
        $this->actingAs($guru->user);

        $pengampu = $guru->pengampu()->firstOrFail();
        $tugas = $pengampu->tugas()->firstOrFail();
        $kuis = $pengampu->kuis()->firstOrFail();
        $jawaban = $kuis->jawaban()->firstOrFail();
        $materi = $pengampu->materi()->firstOrFail();
        $soal = $kuis->soal()->firstOrFail();
        $siswa = $pengampu->daftarSiswa()->first();

        $this->get(route('guru.dashboard'))->assertOk()->assertSee('Rekap Kelas');

        $halaman = [
            route('guru.kelas.index'),
            route('guru.kelas.show', $pengampu),
            route('guru.materi.index', $pengampu), route('guru.materi.create', $pengampu), route('guru.materi.edit', [$pengampu, $materi]),
            route('guru.tugas.index', $pengampu), route('guru.tugas.create', $pengampu),
            route('guru.tugas.show', [$pengampu, $tugas]), route('guru.tugas.show', [$pengampu, $tugas, 'status' => 'belum']),
            route('guru.tugas.edit', [$pengampu, $tugas]),
            route('guru.kuis.index', $pengampu), route('guru.kuis.create', $pengampu),
            route('guru.kuis.show', [$pengampu, $kuis]), route('guru.kuis.edit', [$pengampu, $kuis]),
            route('guru.kuis.hasil', [$pengampu, $kuis]), route('guru.kuis.koreksi', [$pengampu, $kuis, $jawaban]),
            route('guru.presensi.index', $pengampu), route('guru.presensi.form', $pengampu),
            route('guru.presensi.form', [$pengampu, 'pertemuan' => 1]),
            route('guru.nilai.index', $pengampu),
            route('guru.forum.index', $pengampu),
            route('guru.monitoring.index', $pengampu), route('guru.monitoring.siswa', [$pengampu, $siswa]),
            route('guru.pengumuman.index'),
            route('guru.wali.rekap'), route('guru.wali.surat-izin'), route('guru.wali.surat-izin', ['status' => 'semua']),
        ];

        foreach ($halaman as $url) {
            $this->get($url)->assertOk();
        }

        // Soal terkunci karena kuis dummy sudah dikerjakan → diarahkan kembali.
        $this->get(route('guru.soal.edit', [$pengampu, $kuis, $soal]))->assertRedirect(route('guru.kuis.show', [$pengampu, $kuis]));
        $this->get(route('guru.soal.template'))->assertDownload('template_import_soal.xlsx');
    }
}
