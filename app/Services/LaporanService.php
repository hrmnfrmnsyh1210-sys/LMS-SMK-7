<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\OrangTua;
use App\Models\Pengampu;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Data laporan dalam satu bentuk tabel generik, dipakai untuk pratinjau,
 * ekspor PDF/Excel, dan analisis AI. Berlaku untuk tahun ajaran mana pun (riwayat).
 *
 * @phpstan-type Laporan array{judul: string, subjudul: string, info: array<string, string>, kolom: list<string>, baris: list<list<mixed>>, ringkasan: array<string, string>, grafik: ?array<string, mixed>}
 */
class LaporanService
{
    /** Jenis laporan tingkat sekolah (operator & kepala sekolah). */
    public const JENIS_SEKOLAH = [
        'akademik' => 'Akademik Semester',
        'kesiswaan' => 'Data Kesiswaan',
        'kehadiran' => 'Kehadiran Kelas',
        'nilai' => 'Nilai Kelas',
    ];

    /** Jenis laporan guru: per mapel yang diampu, dan per kelas bagi wali kelas. */
    public const JENIS_GURU = [
        'nilai_mapel' => 'Nilai Mapel',
        'kehadiran_mapel' => 'Kehadiran Mapel',
        'nilai_kelas' => 'Nilai Kelas Wali',
        'kehadiran_kelas' => 'Kehadiran Kelas Wali',
    ];

    public function __construct(private RankingService $ranking) {}

    /**
     * Rekap per kelas: kehadiran, rata-rata nilai, dan ketuntasan siswa.
     *
     * @return Laporan
     */
    public function akademik(TahunAjaran $tahun): array
    {
        $kkm = $this->kkm();
        $kelas = Kelas::where('tahun_ajaran_id', $tahun->id)->with('waliKelas')->withCount('siswa')->orderBy('nama_kelas')->get();

        $presensi = Presensi::join('pengampu', 'pengampu.id', '=', 'presensi.pengampu_id')
            ->where('pengampu.tahun_ajaran_id', $tahun->id)
            ->selectRaw('pengampu.kelas_id, presensi.status, COUNT(*) as jumlah')
            ->groupBy('pengampu.kelas_id', 'presensi.status')
            ->get()
            ->groupBy('kelas_id')
            ->map(fn ($b) => $b->pluck('jumlah', 'status'));

        // Rata-rata nilai akhir tiap siswa per kelas.
        $nilaiSiswa = Nilai::join('pengampu', 'pengampu.id', '=', 'nilai.pengampu_id')
            ->where('pengampu.tahun_ajaran_id', $tahun->id)
            ->whereNotNull('nilai.nilai_akhir')
            ->selectRaw('pengampu.kelas_id, nilai.siswa_id, AVG(nilai.nilai_akhir) as rata')
            ->groupBy('pengampu.kelas_id', 'nilai.siswa_id')
            ->get()
            ->groupBy('kelas_id');

        $semuaPresensi = ['hadir' => 0, 'total' => 0];
        $baris = $kelas->values()->map(function (Kelas $k, $i) use ($presensi, $nilaiSiswa, $kkm, &$semuaPresensi) {
            $p = $presensi->get($k->id, collect());
            $total = (int) $p->sum();
            $semuaPresensi['hadir'] += (int) ($p['hadir'] ?? 0);
            $semuaPresensi['total'] += $total;
            $rata = $nilaiSiswa->get($k->id, collect())->pluck('rata')->map(fn ($r) => (float) $r);

            return [
                $i + 1,
                $k->nama_kelas,
                $k->waliKelas?->nama ?? '-',
                $k->siswa_count,
                $this->persen($p['hadir'] ?? 0, $total),
                (int) ($p['izin'] ?? 0),
                (int) ($p['sakit'] ?? 0),
                (int) ($p['alpa'] ?? 0),
                $rata->isEmpty() ? null : round($rata->avg(), 2),
                $rata->filter(fn ($r) => $r >= $kkm)->count(),
                $rata->filter(fn ($r) => $r < $kkm)->count(),
            ];
        })->all();

        $rataSekolah = $nilaiSiswa->flatten(1)->pluck('rata');

        return $this->laporan(
            'Laporan Akademik Semester',
            $tahun,
            ['KKM' => (string) $kkm],
            ['No', 'Kelas', 'Wali Kelas', 'Siswa', 'Hadir (%)', 'Izin', 'Sakit', 'Alpa', 'Rata-rata Nilai', 'Tuntas', 'Belum Tuntas'],
            $baris,
            [
                'Jumlah kelas' => (string) $kelas->count(),
                'Jumlah siswa' => (string) $kelas->sum('siswa_count'),
                'Kehadiran sekolah' => ($this->persen($semuaPresensi['hadir'], $semuaPresensi['total']) ?? '-').'%',
                'Rata-rata nilai sekolah' => $rataSekolah->isEmpty() ? '-' : (string) round($rataSekolah->avg(), 2),
                'Siswa di bawah KKM' => (string) $rataSekolah->filter(fn ($r) => $r < $kkm)->count(),
            ],
            grafik: ['nama' => 'Rata-rata nilai per kelas', 'label' => 1, 'nilai' => 8, 'maks' => 100, 'garis' => $kkm,
                'sorotan' => ['label' => 'kehadiran sekolah', 'nilai' => $this->persen($semuaPresensi['hadir'], $semuaPresensi['total'])]],
        );
    }

    /**
     * Sebaran siswa per kelas (jenis kelamin) dan ringkasan akun.
     *
     * @return Laporan
     */
    public function kesiswaan(TahunAjaran $tahun): array
    {
        $kelas = Kelas::where('tahun_ajaran_id', $tahun->id)->with(['jurusan', 'waliKelas'])
            ->withCount([
                'siswa as laki_count' => fn ($q) => $q->where('jenis_kelamin', 'L'),
                'siswa as perempuan_count' => fn ($q) => $q->where('jenis_kelamin', 'P'),
            ])
            ->orderBy('nama_kelas')
            ->get();

        $baris = $kelas->values()->map(fn (Kelas $k, $i) => [
            $i + 1,
            $k->nama_kelas,
            $k->jurusan?->nama ?? '-',
            $k->waliKelas?->nama ?? '-',
            $k->laki_count,
            $k->perempuan_count,
            $k->laki_count + $k->perempuan_count,
        ])->all();

        return $this->laporan(
            'Laporan Data Kesiswaan',
            $tahun,
            [],
            ['No', 'Kelas', 'Jurusan', 'Wali Kelas', 'Laki-laki', 'Perempuan', 'Jumlah'],
            $baris,
            [
                'Siswa di kelas' => (string) ($kelas->sum('laki_count') + $kelas->sum('perempuan_count')),
                'Siswa aktif (seluruh)' => (string) Siswa::where('status', 'aktif')->count(),
                'Siswa lulus' => (string) Siswa::where('status', 'lulus')->count(),
                'Guru' => (string) Guru::count(),
                'Akun orang tua' => (string) OrangTua::count(),
                'Akun nonaktif' => (string) User::where('status', 'nonaktif')->count(),
            ],
            grafik: ['nama' => 'Jumlah siswa per kelas', 'label' => 1, 'nilai' => 6],
        );
    }

    /**
     * Kehadiran tiap siswa di satu kelas (semua mapel).
     *
     * @return Laporan
     */
    public function kehadiranKelas(Kelas $kelas): array
    {
        $kelas->loadMissing(['tahunAjaran', 'waliKelas']);

        return $this->tabelKehadiran(
            "Laporan Kehadiran Kelas {$kelas->nama_kelas}",
            $kelas->tahunAjaran,
            ['Wali kelas' => $kelas->waliKelas?->nama ?? '-'],
            $kelas->siswa()->orderBy('nama')->get(),
            Presensi::whereIn('pengampu_id', $kelas->pengampu()->select('id')),
        );
    }

    /**
     * Nilai akhir tiap siswa per mapel, rata-rata, dan peringkat kelas.
     *
     * @return Laporan
     */
    public function nilaiKelas(Kelas $kelas): array
    {
        $kelas->loadMissing(['tahunAjaran', 'waliKelas', 'pengampu.mapel']);
        $kkm = $this->kkm();

        $pengampu = $kelas->pengampu->sortBy('mapel.nama_mapel')->values();
        $nilai = Nilai::whereIn('pengampu_id', $pengampu->pluck('id'))->get()->groupBy('siswa_id')->map->keyBy('pengampu_id');

        $baris = $kelas->siswa()->orderBy('nama')->get()->map(function (Siswa $s) use ($nilai, $pengampu) {
            $milik = $nilai->get($s->id, collect());
            $akhir = $milik->pluck('nilai_akhir')->filter(fn ($n) => $n !== null)->map(fn ($n) => (float) $n);

            return [
                'siswa' => $s,
                'mapel' => $pengampu->map(fn (Pengampu $p) => $milik->get($p->id)?->nilai_akhir !== null ? (float) $milik->get($p->id)->nilai_akhir : null)->all(),
                'rata' => $akhir->isEmpty() ? null : round($akhir->avg(), 2),
            ];
        });

        $peringkat = $this->ranking->beriPeringkat($baris)->sortBy('siswa.nama')->values();
        $rata = $peringkat->pluck('rata')->filter(fn ($r) => $r !== null);

        return $this->laporan(
            "Laporan Nilai Kelas {$kelas->nama_kelas}",
            $kelas->tahunAjaran,
            ['Wali kelas' => $kelas->waliKelas?->nama ?? '-', 'KKM' => (string) $kkm],
            ['No', 'NIS', 'Nama', ...$pengampu->map(fn (Pengampu $p) => $p->mapel->kode ?: $p->mapel->nama_mapel)->all(), 'Rata-rata', 'Peringkat'],
            $peringkat->map(fn ($b, $i) => [$i + 1, $b['siswa']->nis, $b['siswa']->nama, ...$b['mapel'], $b['rata'], $b['peringkat']])->all(),
            [
                'Jumlah siswa' => (string) $peringkat->count(),
                'Rata-rata kelas' => $rata->isEmpty() ? '-' : (string) round($rata->avg(), 2),
                'Tertinggi' => $rata->isEmpty() ? '-' : (string) $rata->max(),
                'Terendah' => $rata->isEmpty() ? '-' : (string) $rata->min(),
                'Di bawah KKM' => (string) $rata->filter(fn ($r) => $r < $kkm)->count(),
                'Keterangan mapel' => $pengampu->map(fn (Pengampu $p) => ($p->mapel->kode ?: $p->mapel->nama_mapel).' = '.$p->mapel->nama_mapel)->implode(', ') ?: '-',
            ],
            grafik: ['nama' => 'Rata-rata nilai per siswa', 'label' => 2, 'nilai' => count($pengampu) + 3, 'maks' => 100, 'garis' => $kkm,
                'sorotan' => ['label' => 'rata-rata kelas', 'nilai' => $rata->isEmpty() ? null : round($rata->avg(), 1), 'satuan' => '', 'acuan' => $kkm]],
        );
    }

    /**
     * Rincian nilai satu mapel (pengampu).
     *
     * @return Laporan
     */
    public function nilaiMapel(Pengampu $pengampu): array
    {
        $pengampu->loadMissing(['mapel', 'kelas', 'guru', 'tahunAjaran']);
        $kkm = $this->kkm();
        $nilai = $pengampu->nilai()->get()->keyBy('siswa_id');
        $bobot = $pengampu->bobot();

        $baris = $pengampu->daftarSiswa()->values()->map(function (Siswa $s, $i) use ($nilai, $kkm) {
            $n = $nilai->get($s->id);
            $akhir = $n?->nilai_akhir !== null ? (float) $n->nilai_akhir : null;

            return [
                $i + 1, $s->nis, $s->nama,
                $this->angka($n?->nilai_tugas), $this->angka($n?->nilai_kuis), $this->angka($n?->nilai_uts), $this->angka($n?->nilai_uas),
                $akhir,
                $akhir === null ? '-' : ($akhir >= $kkm ? 'Tuntas' : 'Belum tuntas'),
            ];
        });

        $akhir = $baris->pluck(7)->filter(fn ($n) => $n !== null);

        return $this->laporan(
            "Laporan Nilai {$pengampu->mapel->nama_mapel} — {$pengampu->kelas->nama_kelas}",
            $pengampu->tahunAjaran,
            [
                'Guru' => $pengampu->guru->nama,
                'Bobot' => "tugas {$bobot->bobot_tugas}%, kuis {$bobot->bobot_kuis}%, UTS {$bobot->bobot_uts}%, UAS {$bobot->bobot_uas}%",
                'KKM' => (string) $kkm,
            ],
            ['No', 'NIS', 'Nama', 'Tugas', 'Kuis', 'UTS', 'UAS', 'Nilai Akhir', 'Keterangan'],
            $baris->all(),
            [
                'Jumlah siswa' => (string) $baris->count(),
                'Rata-rata' => $akhir->isEmpty() ? '-' : (string) round($akhir->avg(), 2),
                'Tertinggi' => $akhir->isEmpty() ? '-' : (string) $akhir->max(),
                'Terendah' => $akhir->isEmpty() ? '-' : (string) $akhir->min(),
                'Tuntas' => (string) $akhir->filter(fn ($n) => $n >= $kkm)->count(),
                'Belum tuntas' => (string) $akhir->filter(fn ($n) => $n < $kkm)->count(),
            ],
            grafik: ['nama' => 'Nilai akhir per siswa', 'label' => 2, 'nilai' => 7, 'maks' => 100, 'garis' => $kkm,
                'sorotan' => ['label' => 'rata-rata nilai akhir', 'nilai' => $akhir->isEmpty() ? null : round($akhir->avg(), 1), 'satuan' => '', 'acuan' => $kkm]],
        );
    }

    /**
     * Kehadiran tiap siswa pada satu mapel (pengampu).
     *
     * @return Laporan
     */
    public function kehadiranMapel(Pengampu $pengampu): array
    {
        $pengampu->loadMissing(['mapel', 'kelas', 'guru', 'tahunAjaran']);
        $pertemuan = $pengampu->presensi()->distinct()->count('pertemuan_ke');

        return $this->tabelKehadiran(
            "Laporan Kehadiran {$pengampu->mapel->nama_mapel} — {$pengampu->kelas->nama_kelas}",
            $pengampu->tahunAjaran,
            ['Guru' => $pengampu->guru->nama, 'Jumlah pertemuan' => (string) $pertemuan],
            $pengampu->daftarSiswa(),
            Presensi::where('pengampu_id', $pengampu->id),
        );
    }

    /**
     * @param  array<string, string>  $info
     * @param  Collection<int, Siswa>  $siswa
     * @param  Builder<Presensi>  $presensi
     * @return Laporan
     */
    private function tabelKehadiran(string $judul, TahunAjaran $tahun, array $info, Collection $siswa, Builder $presensi): array
    {
        $rekap = $presensi->whereIn('siswa_id', $siswa->pluck('id'))
            ->selectRaw('siswa_id, status, COUNT(*) as jumlah')
            ->groupBy('siswa_id', 'status')
            ->get()
            ->groupBy('siswa_id')
            ->map(fn ($b) => $b->pluck('jumlah', 'status'));

        $baris = $siswa->values()->map(function (Siswa $s, $i) use ($rekap) {
            $r = $rekap->get($s->id, collect());
            $total = (int) $r->sum();

            return [
                $i + 1, $s->nis, $s->nama,
                (int) ($r['hadir'] ?? 0), (int) ($r['izin'] ?? 0), (int) ($r['sakit'] ?? 0), (int) ($r['alpa'] ?? 0),
                $total,
                $this->persen($r['hadir'] ?? 0, $total),
            ];
        });

        $persen = $baris->pluck(8)->filter(fn ($p) => $p !== null);

        return $this->laporan(
            $judul,
            $tahun,
            $info,
            ['No', 'NIS', 'Nama', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Total', 'Kehadiran (%)'],
            $baris->all(),
            [
                'Jumlah siswa' => (string) $baris->count(),
                'Rata-rata kehadiran' => $persen->isEmpty() ? '-' : round($persen->avg(), 1).'%',
                'Siswa alpa ≥ 3' => (string) $baris->filter(fn ($b) => $b[6] >= 3)->count(),
                'Total alpa' => (string) $baris->sum(6),
            ],
            grafik: ['nama' => 'Kehadiran per siswa', 'label' => 2, 'nilai' => 8, 'satuan' => '%', 'maks' => 100,
                'sorotan' => ['label' => 'rata-rata kehadiran', 'nilai' => $persen->isEmpty() ? null : round($persen->avg(), 1)]],
        );
    }

    /**
     * @param  array<string, string>  $info
     * @param  list<string>  $kolom
     * @param  list<list<mixed>>  $baris
     * @param  array<string, string>  $ringkasan
     * @return Laporan
     */
    private function laporan(string $judul, TahunAjaran $tahun, array $info, array $kolom, array $baris, array $ringkasan, ?array $grafik = null): array
    {
        return [
            'grafik' => $grafik ? $this->dataGrafik($grafik, $baris) : null,
            'judul' => $judul,
            'subjudul' => 'Tahun ajaran '.$tahun->label,
            'info' => $info,
            'kolom' => $kolom,
            'baris' => $baris,
            'ringkasan' => $ringkasan,
        ];
    }

    /**
     * Ambil kolom label & nilai dari baris tabel untuk grafik batang di halaman laporan.
     *
     * @param  array{nama: string, label: int, nilai: int, satuan?: string, maks?: int, garis?: float, sorotan?: array<string, mixed>}  $grafik
     * @param  list<list<mixed>>  $baris
     * @return array{nama: string, label: list<string>, nilai: list<?float>, satuan: string, maks: ?int, garis: ?array{label: string, nilai: float}, sorotan: ?array<string, mixed>}
     */
    private function dataGrafik(array $grafik, array $baris): array
    {
        return [
            'nama' => $grafik['nama'],
            // Kolom 2 = nama siswa: dipersingkat (dua kata pertama) agar sumbu tetap terbaca.
            'label' => array_map(fn ($b) => $grafik['label'] === 2
                ? implode(' ', array_slice(explode(' ', (string) $b[2]), 0, 2))
                : (string) $b[$grafik['label']], $baris),
            'nilai' => array_map(fn ($b) => $b[$grafik['nilai']] === null ? null : (float) $b[$grafik['nilai']], $baris),
            'satuan' => $grafik['satuan'] ?? '',
            'maks' => $grafik['maks'] ?? null,
            'garis' => isset($grafik['garis']) ? ['label' => 'KKM', 'nilai' => $grafik['garis']] : null,
            'sorotan' => $grafik['sorotan'] ?? null,
        ];
    }

    private function kkm(): float
    {
        return (float) pengaturan('kkm', 75);
    }

    private function persen(int|string $bagian, int $total): ?float
    {
        return $total > 0 ? round((int) $bagian / $total * 100, 1) : null;
    }

    private function angka(mixed $nilai): ?float
    {
        return $nilai === null ? null : (float) $nilai;
    }
}
