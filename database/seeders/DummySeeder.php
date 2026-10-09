<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Kepsek;
use App\Models\Mapel;
use App\Models\Pengampu;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data dummy realistis: 1 tahun ajaran aktif, 3 jurusan, 6 kelas,
 * 10 mapel, 8 guru, 60 siswa, plotting pengampu & jadwal tanpa bentrok.
 * Semua akun dummy berpassword "password".
 */
class DummySeeder extends Seeder
{
    private const SLOT = [
        ['07:00', '08:30'],
        ['08:30', '10:00'],
        ['10:15', '11:45'],
        ['12:30', '14:00'],
    ];

    public function run(): void
    {
        fake()->seed(2026);

        Pengaturan::simpan('nama_sekolah', 'SMK Negeri 7');
        Pengaturan::simpan('alamat_sekolah', 'Jl. Pendidikan No. 7');
        Pengaturan::simpan('npsn', '20212345');
        foreach (Pengaturan::DEFAULT as $kunci => $nilai) {
            Pengaturan::firstOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
        }

        // Akun operator & kepala sekolah
        User::factory()->create(['username' => 'operator', 'role' => 'operator'])
            ->operator()->create(['nama' => 'Operator Sekolah', 'no_hp' => '081200000001']);

        Kepsek::factory()->create([
            'user_id' => User::factory()->create(['username' => 'kepsek', 'role' => 'kepsek'])->id,
            'nip' => '197001011995031001',
            'nama' => 'Drs. Hadi Prasetyo, M.Pd.',
            'jenis_kelamin' => 'L',
        ]);

        // Tahun ajaran
        TahunAjaran::create(['nama' => '2025/2026', 'semester' => 'genap', 'status_aktif' => false]);
        $tahun = TahunAjaran::create(['nama' => '2026/2027', 'semester' => 'ganjil', 'status_aktif' => true]);

        // Jurusan
        $jurusan = collect([
            ['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak'],
            ['kode' => 'TKJ', 'nama' => 'Teknik Komputer dan Jaringan'],
            ['kode' => 'AKL', 'nama' => 'Akuntansi dan Keuangan Lembaga'],
        ])->mapWithKeys(fn ($j) => [$j['kode'] => Jurusan::create($j)]);

        // Mapel
        $mapel = collect([
            ['kode' => 'PAI', 'nama_mapel' => 'Pendidikan Agama Islam'],
            ['kode' => 'PKN', 'nama_mapel' => 'Pendidikan Pancasila'],
            ['kode' => 'BIN', 'nama_mapel' => 'Bahasa Indonesia'],
            ['kode' => 'MTK', 'nama_mapel' => 'Matematika'],
            ['kode' => 'BIG', 'nama_mapel' => 'Bahasa Inggris'],
            ['kode' => 'SEJ', 'nama_mapel' => 'Sejarah'],
            ['kode' => 'PJOK', 'nama_mapel' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan'],
            ['kode' => 'INF', 'nama_mapel' => 'Informatika'],
            ['kode' => 'DDK', 'nama_mapel' => 'Dasar-Dasar Program Keahlian'],
            ['kode' => 'PKK', 'nama_mapel' => 'Projek Kreatif dan Kewirausahaan'],
        ])->mapWithKeys(fn ($m) => [$m['kode'] => Mapel::create($m)]);

        // 8 guru, NIP 198001012010011001 s.d. ...008
        $daftarGuru = collect([
            ['Ahmad Fauzi, S.Pd.I.', 'L'], ['Sri Wahyuni, S.Pd.', 'P'], ['Bambang Sutrisno, S.Pd.', 'L'],
            ['Rina Kartika, S.Pd.', 'P'], ['Joko Susilo, S.Pd.', 'L'], ['Dewi Lestari, S.Kom.', 'P'],
            ['Andi Saputra, S.T.', 'L'], ['Fitri Handayani, S.E.', 'P'],
        ])->values()->map(function ($g, $i) {
            $nip = '19800101201001100'.($i + 1);

            return Guru::factory()->create(['nip' => $nip, 'nama' => $g[0], 'jenis_kelamin' => $g[1]]);
        });

        // Guru pengampu per mapel (indeks guru)
        $guruMapel = [
            'PAI' => 0, 'PKN' => 0, 'BIN' => 1, 'SEJ' => 1, 'MTK' => 2,
            'BIG' => 3, 'PJOK' => 4, 'INF' => 5, 'DDK' => 6, 'PKK' => 7,
        ];

        // 6 kelas
        $kelas = collect([
            ['X RPL 1', 'X', 'RPL'], ['X TKJ 1', 'X', 'TKJ'], ['X AKL 1', 'X', 'AKL'],
            ['XI RPL 1', 'XI', 'RPL'], ['XI TKJ 1', 'XI', 'TKJ'], ['XII RPL 1', 'XII', 'RPL'],
        ])->map(fn ($k, $i) => Kelas::create([
            'nama_kelas' => $k[0],
            'tingkat' => $k[1],
            'jurusan_id' => $jurusan[$k[2]]->id,
            'wali_kelas_id' => $daftarGuru[$i + 1]->id,
            'tahun_ajaran_id' => $tahun->id,
        ]));

        // 60 siswa (10 per kelas), NIS 26001 s.d. 26060
        $nomor = 1;
        foreach ($kelas as $k) {
            for ($i = 0; $i < 10; $i++, $nomor++) {
                $nis = (string) (26000 + $nomor);
                $siswa = Siswa::factory()->create([
                    'nis' => $nis,
                    'nisn' => '00'.str_pad((string) (91000000 + $nomor), 8, '0', STR_PAD_LEFT),
                ]);
                KelasSiswa::create(['kelas_id' => $k->id, 'siswa_id' => $siswa->id, 'tahun_ajaran_id' => $tahun->id]);
            }
        }

        // Pengampu: setiap kelas mendapat 10 mapel
        $pengampuPerKelas = [];
        foreach ($kelas as $k) {
            foreach ($mapel as $kode => $m) {
                $pengampuPerKelas[$k->id][] = Pengampu::create([
                    'guru_id' => $daftarGuru[$guruMapel[$kode]]->id,
                    'mapel_id' => $m->id,
                    'kelas_id' => $k->id,
                    'tahun_ajaran_id' => $tahun->id,
                ]);
            }
        }

        $this->buatJadwal($kelas, $pengampuPerKelas);
    }

    /** Jadwal satu sesi per pengampu per minggu, dijamin tidak bentrok. */
    private function buatJadwal($kelas, array $pengampuPerKelas): void
    {
        $slotTerpakaiKelas = [];
        $slotTerpakaiGuru = [];
        $daftarSlot = [];
        foreach (Jadwal::HARI as $hari) {
            foreach (self::SLOT as $s => $_) {
                $daftarSlot[] = [$hari, $s];
            }
        }

        foreach ($kelas->values() as $urutan => $k) {
            foreach ($pengampuPerKelas[$k->id] as $i => $p) {
                // Geser titik awal per kelas agar jadwal tersebar.
                for ($n = 0; $n < count($daftarSlot); $n++) {
                    [$hari, $s] = $daftarSlot[($i * 2 + $urutan + $n) % count($daftarSlot)];
                    $kunci = "{$hari}-{$s}";
                    if (isset($slotTerpakaiKelas[$k->id][$kunci]) || isset($slotTerpakaiGuru[$p->guru_id][$kunci])) {
                        continue;
                    }

                    Jadwal::create([
                        'pengampu_id' => $p->id,
                        'hari' => $hari,
                        'jam_mulai' => self::SLOT[$s][0],
                        'jam_selesai' => self::SLOT[$s][1],
                        'ruang' => 'R. '.$k->nama_kelas,
                    ]);
                    $slotTerpakaiKelas[$k->id][$kunci] = true;
                    $slotTerpakaiGuru[$p->guru_id][$kunci] = true;
                    break;
                }
            }
        }
    }
}
