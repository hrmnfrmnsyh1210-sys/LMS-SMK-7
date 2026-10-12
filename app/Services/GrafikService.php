<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\LogAktivitas;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\PengumpulanTugas;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Data grafik dashboard (operator, kepala sekolah, guru). Setiap metode menerima
 * daftar pengampu sebagai cakupan sehingga bisa dipakai untuk seluruh sekolah maupun satu guru.
 */
class GrafikService
{
    /**
     * Persentase hadir & alpa per bulan (maks. 12 bulan), atau per minggu bila data presensi kurang dari 3 bulan.
     *
     * @param  Collection<int, int>  $pengampuIds
     * @return array{label: list<string>, seri: list<array{nama: string, nilai: list<?float>}>}
     */
    public function trenKehadiran(Collection $pengampuIds): array
    {
        $awalData = Presensi::whereIn('pengampu_id', $pengampuIds)->min('tanggal');
        if (! $awalData) {
            return ['label' => [], 'seri' => [['nama' => 'Hadir', 'nilai' => []]]];
        }

        // Data kurang dari 3 bulan → per minggu agar tren tetap terlihat sebagai garis.
        $awalData = Carbon::parse($awalData);
        $mingguan = $awalData->gt(now()->startOfMonth()->subMonths(2));

        $periode = collect();
        $awal = $mingguan ? $awalData->copy()->startOfWeek() : $awalData->copy()->startOfMonth()->max(now()->startOfMonth()->subMonths(11));
        for ($p = $awal->copy(); $p->lte(now()); $mingguan ? $p->addWeek() : $p->addMonth()) {
            $periode->push($p->copy());
        }

        $kunci = fn (Carbon $t) => $mingguan ? $t->copy()->startOfWeek()->toDateString() : $t->format('Y-m');
        $rekap = Presensi::whereIn('pengampu_id', $pengampuIds)
            ->where('tanggal', '>=', $awal)
            ->get(['tanggal', 'status'])
            ->groupBy(fn (Presensi $p) => $kunci($p->tanggal));

        $persen = fn (string $status) => $periode->map(function (Carbon $p) use ($rekap, $kunci, $status) {
            $baris = $rekap->get($kunci($p));

            return $baris && $baris->count() ? round($baris->where('status', $status)->count() / $baris->count() * 100, 1) : null;
        })->all();

        return [
            'label' => $periode->map(fn (Carbon $p) => $p->locale('id')->translatedFormat($mingguan ? 'j M' : 'M Y'))->all(),
            'seri' => [
                ['nama' => 'Hadir', 'nilai' => $persen('hadir')],
                ['nama' => 'Alpa', 'nilai' => $persen('alpa')],
            ],
        ];
    }

    /**
     * Rata-rata nilai akhir per kelas (tahun ajaran aktif).
     *
     * @return array{label: list<string>, nilai: list<?float>}
     */
    public function rataNilaiPerKelas(): array
    {
        $kelas = Kelas::tahunAktif()->orderBy('nama_kelas')->get(['id', 'nama_kelas']);
        $rata = Nilai::join('pengampu', 'pengampu.id', '=', 'nilai.pengampu_id')
            ->whereIn('pengampu.kelas_id', $kelas->pluck('id'))
            ->whereNotNull('nilai.nilai_akhir')
            ->selectRaw('pengampu.kelas_id, AVG(nilai.nilai_akhir) as rata')
            ->groupBy('pengampu.kelas_id')
            ->pluck('rata', 'kelas_id');

        return [
            'label' => $kelas->pluck('nama_kelas')->all(),
            'nilai' => $kelas->map(fn ($k) => isset($rata[$k->id]) ? round((float) $rata[$k->id], 1) : null)->all(),
        ];
    }

    /**
     * Rata-rata nilai akhir tiap kelas ajar (pengampu) seorang guru.
     *
     * @param  Collection<int, int>  $pengampuIds
     * @return array{label: list<string>, nilai: list<?float>}
     */
    public function rataNilaiPerPengampu(Collection $pengampuIds): array
    {
        $pengampu = Pengampu::with(['mapel', 'kelas'])->whereIn('id', $pengampuIds)->get()->sortBy('label')->values();
        $rata = Nilai::whereIn('pengampu_id', $pengampuIds)->whereNotNull('nilai_akhir')
            ->selectRaw('pengampu_id, AVG(nilai_akhir) as rata')
            ->groupBy('pengampu_id')
            ->pluck('rata', 'pengampu_id');

        return [
            'label' => $pengampu->map(fn (Pengampu $p) => ($p->mapel->kode ?: $p->mapel->nama_mapel).' · '.$p->kelas->nama_kelas)->all(),
            'nilai' => $pengampu->map(fn (Pengampu $p) => isset($rata[$p->id]) ? round((float) $rata[$p->id], 1) : null)->all(),
        ];
    }

    /**
     * Jumlah siswa per kelas (tahun ajaran aktif).
     *
     * @return array{label: list<string>, nilai: list<int>}
     */
    public function siswaPerKelas(): array
    {
        $kelas = Kelas::tahunAktif()->withCount('siswa')->orderBy('nama_kelas')->get();

        return ['label' => $kelas->pluck('nama_kelas')->all(), 'nilai' => $kelas->pluck('siswa_count')->all()];
    }

    /**
     * Login & aktivitas lain per hari selama $hari hari terakhir.
     *
     * @return array{label: list<string>, seri: list<array{nama: string, nilai: list<int>}>}
     */
    public function aktivitasHarian(int $hari = 14): array
    {
        $rekap = LogAktivitas::where('created_at', '>=', now()->startOfDay()->subDays($hari - 1))
            ->selectRaw("DATE(created_at) as tanggal, SUM(aksi = 'login') as login, SUM(aksi <> 'login') as lain")
            ->groupBy('tanggal')
            ->get()
            ->keyBy(fn ($r) => Carbon::parse($r->tanggal)->toDateString());

        $tanggal = collect(range($hari - 1, 0))->map(fn ($i) => now()->subDays($i));

        return [
            'label' => $tanggal->map(fn (Carbon $t) => $t->locale('id')->translatedFormat('j M'))->all(),
            'seri' => [
                ['nama' => 'Login', 'nilai' => $tanggal->map(fn (Carbon $t) => (int) ($rekap->get($t->toDateString())?->login ?? 0))->all()],
                ['nama' => 'Aktivitas lain', 'nilai' => $tanggal->map(fn (Carbon $t) => (int) ($rekap->get($t->toDateString())?->lain ?? 0))->all()],
            ],
        ];
    }

    /**
     * Pengumpulan tugas per minggu (8 minggu terakhir) untuk tugas milik pengampu tertentu.
     *
     * @param  Collection<int, int>  $pengampuIds
     * @return array{label: list<string>, seri: list<array{nama: string, nilai: list<int>}>}
     */
    public function pengumpulanMingguan(Collection $pengampuIds, int $minggu = 8): array
    {
        $awal = now()->startOfWeek()->subWeeks($minggu - 1);
        $kumpulan = PengumpulanTugas::whereIn('tugas_id', Tugas::whereIn('pengampu_id', $pengampuIds)->select('id'))
            ->where('waktu_kumpul', '>=', $awal)
            ->get(['waktu_kumpul', 'nilai']);

        $pekan = collect(range(0, $minggu - 1))->map(fn ($i) => $awal->copy()->addWeeks($i));
        $hitung = fn (Carbon $mulai, bool $dinilai) => $kumpulan
            ->filter(fn ($k) => $k->waktu_kumpul->between($mulai, $mulai->copy()->endOfWeek()) && (! $dinilai || $k->nilai !== null))
            ->count();

        return [
            'label' => $pekan->map(fn (Carbon $p) => $p->locale('id')->translatedFormat('j M'))->all(),
            'seri' => [
                ['nama' => 'Dikumpulkan', 'nilai' => $pekan->map(fn ($p) => $hitung($p, false))->all()],
                ['nama' => 'Sudah dinilai', 'nilai' => $pekan->map(fn ($p) => $hitung($p, true))->all()],
            ],
        ];
    }

    /**
     * Capaian pembelajaran dalam persen: kehadiran, ketuntasan KKM, pengumpulan tugas, kelengkapan nilai.
     *
     * @param  Collection<int, int>  $pengampuIds
     * @return list<array{label: string, nilai: ?float, keterangan: string}>
     */
    public function capaian(Collection $pengampuIds): array
    {
        $kkm = (float) pengaturan('kkm', 75);
        $pengampu = Pengampu::whereIn('id', $pengampuIds)->get(['id', 'kelas_id', 'tahun_ajaran_id']);

        $presensi = Presensi::whereIn('pengampu_id', $pengampuIds)->selectRaw("COUNT(*) as total, SUM(status = 'hadir') as hadir")->first();
        $nilai = Nilai::whereIn('pengampu_id', $pengampuIds)->whereNotNull('nilai_akhir')->pluck('nilai_akhir')->map(fn ($n) => (float) $n);

        // Siswa per kelas pengampu → jumlah pengumpulan yang diharapkan & jumlah nilai yang semestinya ada.
        $siswaPerKelas = KelasSiswa::whereIn('kelas_id', $pengampu->pluck('kelas_id'))
            ->where('tahun_ajaran_id', TahunAjaran::aktifId())
            ->whereIn('siswa_id', Siswa::where('status', 'aktif')->select('id'))
            ->selectRaw('kelas_id, COUNT(*) as jumlah')
            ->groupBy('kelas_id')
            ->pluck('jumlah', 'kelas_id');
        $siswaPengampu = $pengampu->mapWithKeys(fn ($p) => [$p->id => (int) ($siswaPerKelas[$p->kelas_id] ?? 0)]);

        $tugas = Tugas::whereIn('pengampu_id', $pengampuIds)->where('deadline', '<=', now())->get(['id', 'pengampu_id']);
        $harapanKumpul = $tugas->sum(fn ($t) => $siswaPengampu[$t->pengampu_id] ?? 0);
        $kumpul = PengumpulanTugas::whereIn('tugas_id', $tugas->pluck('id'))->count();
        $harapanNilai = $siswaPengampu->sum();

        $persen = fn ($bagian, $total) => $total > 0 ? round(min($bagian, $total) / $total * 100, 1) : null;

        return [
            ['label' => 'Kehadiran siswa', 'nilai' => $persen((int) $presensi?->hadir, (int) $presensi?->total), 'keterangan' => ((int) $presensi?->total).' catatan presensi'],
            ['label' => 'Tuntas KKM', 'nilai' => $persen($nilai->filter(fn ($n) => $n >= $kkm)->count(), $nilai->count()), 'keterangan' => 'KKM '.$kkm],
            ['label' => 'Tugas terkumpul', 'nilai' => $persen($kumpul, $harapanKumpul), 'keterangan' => $tugas->count().' tugas lewat tenggat'],
            ['label' => 'Nilai akhir terisi', 'nilai' => $persen($nilai->count(), $harapanNilai), 'keterangan' => $nilai->count().' dari '.$harapanNilai.' nilai'],
        ];
    }

    /**
     * Kesehatan data sekolah untuk operator dalam persen.
     *
     * @return list<array{label: string, nilai: ?float, keterangan: string}>
     */
    public function kesehatanData(): array
    {
        $tahunId = TahunAjaran::aktifId();
        $siswaAktif = Siswa::where('status', 'aktif')->count();
        $siswaBerkelas = Siswa::where('status', 'aktif')->whereHas('kelasSiswa', fn ($q) => $q->where('tahun_ajaran_id', $tahunId))->count();
        $akun = User::count();
        $kelas = Kelas::where('tahun_ajaran_id', $tahunId)->count();

        $persen = fn ($bagian, $total) => $total > 0 ? round($bagian / $total * 100, 1) : null;

        return [
            ['label' => 'Siswa sudah berkelas', 'nilai' => $persen($siswaBerkelas, $siswaAktif), 'keterangan' => "{$siswaBerkelas} dari {$siswaAktif} siswa aktif"],
            ['label' => 'Kelas memiliki wali', 'nilai' => $persen(Kelas::where('tahun_ajaran_id', $tahunId)->whereNotNull('wali_kelas_id')->count(), $kelas), 'keterangan' => "{$kelas} kelas tahun aktif"],
            ['label' => 'Akun aktif', 'nilai' => $persen(User::where('status', 'aktif')->count(), $akun), 'keterangan' => "{$akun} akun"],
            ['label' => 'Akun pernah login', 'nilai' => $persen(User::whereNotNull('last_login')->count(), $akun), 'keterangan' => 'Adopsi sistem'],
        ];
    }
}
