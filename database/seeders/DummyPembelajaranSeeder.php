<?php

namespace Database\Seeders;

use App\Models\JawabanKuis;
use App\Models\Kuis;
use App\Models\Materi;
use App\Models\Pengampu;
use App\Models\PengumpulanTugas;
use App\Models\Presensi;
use App\Models\Soal;
use App\Models\SuratIzin;
use App\Models\Tugas;
use App\Services\KuisService;
use App\Services\NilaiService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Contoh aktivitas pembelajaran untuk setiap pengampu: materi, tugas
 * (sebagian dinilai), kuis selesai (PG + esai), presensi 3 pertemuan,
 * serta surat izin yang menunggu persetujuan wali kelas.
 */
class DummyPembelajaranSeeder extends Seeder
{
    public function run(KuisService $kuisService, NilaiService $nilaiService): void
    {
        // Satu transaksi jauh lebih cepat daripada commit per baris di MySQL.
        DB::transaction(fn () => $this->isi($kuisService, $nilaiService));
    }

    private function isi(KuisService $kuisService, NilaiService $nilaiService): void
    {
        fake()->seed(2027);

        foreach (Pengampu::with(['mapel', 'kelas'])->get() as $pengampu) {
            $siswa = $pengampu->daftarSiswa();
            $mapel = $pengampu->mapel->nama_mapel;

            foreach ([1, 2] as $pertemuan) {
                Materi::create([
                    'pengampu_id' => $pengampu->id,
                    'judul' => "{$mapel} — Pertemuan {$pertemuan}",
                    'deskripsi' => "Ringkasan materi {$mapel} pertemuan {$pertemuan}.",
                    'tautan' => 'https://www.youtube.com/results?search_query='.urlencode($mapel),
                    'pertemuan_ke' => $pertemuan,
                ]);
            }

            // Tugas 1 sudah lewat deadline (sebagian dinilai), tugas 2 masih berjalan.
            $tugasLalu = Tugas::create([
                'pengampu_id' => $pengampu->id, 'judul' => "Latihan 1 {$mapel}",
                'deskripsi' => 'Kerjakan soal latihan pada buku paket halaman 10–12.',
                'deadline' => now()->subDays(3)->setTime(23, 59), 'nilai_maks' => 100,
            ]);
            Tugas::create([
                'pengampu_id' => $pengampu->id, 'judul' => "Rangkuman Bab 2 {$mapel}",
                'deskripsi' => 'Buat rangkuman bab 2 minimal satu halaman.',
                'deadline' => now()->addDays(4)->setTime(23, 59), 'nilai_maks' => 100,
            ]);

            foreach ($siswa as $i => $s) {
                if ($i % 5 === 4) {
                    continue; // sebagian siswa tidak mengumpulkan
                }
                PengumpulanTugas::create([
                    'tugas_id' => $tugasLalu->id, 'siswa_id' => $s->id,
                    'jawaban_teks' => 'Jawaban latihan terlampir.',
                    'waktu_kumpul' => now()->subDays($i % 3 === 0 ? 2 : 4),
                    'nilai' => $i % 4 === 3 ? null : fake()->numberBetween(65, 98),
                ]);
            }

            $this->buatKuis($pengampu, $siswa, $kuisService);

            foreach ([1, 2, 3] as $pertemuan) {
                $tanggal = now()->subWeeks(4 - $pertemuan)->startOfWeek()->toDateString();
                foreach ($siswa as $s) {
                    Presensi::create([
                        'pengampu_id' => $pengampu->id, 'siswa_id' => $s->id,
                        'tanggal' => $tanggal, 'pertemuan_ke' => $pertemuan,
                        'status' => fake()->randomElement(['hadir', 'hadir', 'hadir', 'hadir', 'hadir', 'hadir', 'izin', 'sakit', 'alpa']),
                    ]);
                }
            }

            $nilaiService->hitungRekap($pengampu);
        }

        // Surat izin menunggu untuk setiap kelas
        foreach (Pengampu::with('kelas')->get()->unique('kelas_id') as $pengampu) {
            $s = $pengampu->daftarSiswa()->first();
            if ($s) {
                SuratIzin::create([
                    'siswa_id' => $s->id, 'kelas_id' => $pengampu->kelas_id, 'jenis' => 'sakit',
                    'tanggal_mulai' => now()->toDateString(), 'tanggal_selesai' => now()->addDay()->toDateString(),
                    'keterangan' => 'Demam, dianjurkan istirahat oleh dokter.', 'status' => 'menunggu',
                ]);
            }
        }
    }

    private function buatKuis(Pengampu $pengampu, $siswa, KuisService $kuisService): void
    {
        $kuis = Kuis::create([
            'pengampu_id' => $pengampu->id, 'judul' => "Kuis 1 {$pengampu->mapel->nama_mapel}", 'jenis' => 'kuis',
            'waktu_mulai' => now()->subDays(5)->setTime(7, 0), 'waktu_selesai' => now()->subDays(5)->setTime(15, 0),
            'durasi_menit' => 30, 'acak_soal' => true, 'tampilkan_hasil' => true,
        ]);

        $soal = collect(range(1, 4))->map(fn ($n) => Soal::create([
            'kuis_id' => $kuis->id, 'tipe' => 'pg', 'pertanyaan' => "Soal pilihan ganda nomor {$n}?",
            'opsi_a' => 'Pilihan A', 'opsi_b' => 'Pilihan B', 'opsi_c' => 'Pilihan C', 'opsi_d' => 'Pilihan D',
            'kunci_jawaban' => ['a', 'b', 'c', 'd'][$n - 1], 'bobot' => 1,
        ]))->push(Soal::create([
            'kuis_id' => $kuis->id, 'tipe' => 'esai', 'pertanyaan' => 'Jelaskan pemahamanmu tentang materi ini.', 'bobot' => 2,
        ]));

        foreach ($siswa as $i => $s) {
            if ($i % 6 === 5) {
                continue; // tidak mengerjakan
            }

            $jawaban = JawabanKuis::create([
                'kuis_id' => $kuis->id, 'siswa_id' => $s->id,
                'waktu_mulai' => $kuis->waktu_mulai->copy()->addMinutes(10),
                'waktu_submit' => $kuis->waktu_mulai->copy()->addMinutes(35),
                'jumlah_pindah_tab' => $i % 7 === 0 ? 2 : 0,
            ]);

            foreach ($soal as $so) {
                $jawaban->detail()->create([
                    'soal_id' => $so->id,
                    'jawaban' => $so->isPilihanGanda() ? fake()->randomElement(['a', 'b', 'c', 'd', $so->kunci_jawaban, $so->kunci_jawaban]) : 'Uraian jawaban siswa.',
                ]);
            }

            $kuisService->koreksiOtomatis($jawaban);

            // Sebagian esai sudah dikoreksi guru
            if ($i % 2 === 0) {
                $kuisService->koreksiEsai($jawaban, [$soal->last()->id => fake()->randomElement([1, 1.5, 2])]);
            }
        }
    }
}
