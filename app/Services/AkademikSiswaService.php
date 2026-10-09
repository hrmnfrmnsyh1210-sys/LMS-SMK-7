<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\JawabanKuis;
use App\Models\Kuis;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\PengumpulanTugas;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\Tugas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Data akademik satu siswa pada tahun ajaran aktif. Dipakai halaman siswa
 * dan halaman orang tua yang memantau anaknya.
 */
class AkademikSiswaService
{
    public function __construct(
        private PengerjaanKuisService $pengerjaanKuis,
        private RankingService $ranking,
    ) {}

    /**
     * @return array{tugas: int, terlewat: int, kuis: int, kehadiran: ?float, nilai: ?float}
     */
    public function ringkasan(Siswa $siswa): array
    {
        $pengampuIds = $siswa->pengampu()->pluck('id');

        $presensi = Presensi::where('siswa_id', $siswa->id)->whereIn('pengampu_id', $pengampuIds)
            ->selectRaw("COUNT(*) as total, SUM(status = 'hadir') as hadir")
            ->first();
        $rataNilai = $siswa->nilai()->whereIn('pengampu_id', $pengampuIds)->whereNotNull('nilai_akhir')->avg('nilai_akhir');

        return [
            'tugas' => $this->tugasBelumQuery($siswa, $pengampuIds)->count(),
            'terlewat' => Tugas::whereIn('pengampu_id', $pengampuIds)
                ->where('deadline', '<', now())
                ->whereDoesntHave('pengumpulan', fn ($q) => $q->where('siswa_id', $siswa->id))
                ->count(),
            'kuis' => $this->kuisAktifQuery($siswa, $pengampuIds)->count(),
            'kehadiran' => $presensi?->total ? round($presensi->hadir / $presensi->total * 100, 1) : null,
            'nilai' => $rataNilai !== null ? round((float) $rataNilai, 1) : null,
        ];
    }

    /**
     * Tugas yang belum dikumpulkan dan belum lewat deadline, urut deadline terdekat.
     *
     * @param  Collection<int, int>  $pengampuIds
     * @return Builder<Tugas>
     */
    public function tugasBelumQuery(Siswa $siswa, Collection $pengampuIds)
    {
        return Tugas::with('pengampu.mapel')
            ->whereIn('pengampu_id', $pengampuIds)
            ->where('deadline', '>=', now())
            ->whereDoesntHave('pengumpulan', fn ($q) => $q->where('siswa_id', $siswa->id))
            ->orderBy('deadline');
    }

    /**
     * Kuis yang sedang dibuka dan belum dikirim siswa.
     *
     * @param  Collection<int, int>  $pengampuIds
     * @return Builder<Kuis>
     */
    public function kuisAktifQuery(Siswa $siswa, Collection $pengampuIds)
    {
        return Kuis::with('pengampu.mapel')
            ->whereIn('pengampu_id', $pengampuIds)
            ->where('waktu_mulai', '<=', now())
            ->where('waktu_selesai', '>=', now())
            ->whereDoesntHave('jawaban', fn ($q) => $q->where('siswa_id', $siswa->id)->whereNotNull('waktu_submit'))
            ->orderBy('waktu_selesai');
    }

    /**
     * Jadwal pelajaran kelas siswa, dikelompokkan per hari.
     *
     * @return Collection<string, Collection<int, Jadwal>>
     */
    public function jadwal(Siswa $siswa): Collection
    {
        return Jadwal::with(['pengampu.mapel', 'pengampu.guru'])
            ->whereIn('pengampu_id', $siswa->pengampu()->select('id'))
            ->orderBy('jam_mulai')
            ->get()
            ->groupBy('hari');
    }

    /**
     * Rekap presensi per mapel, total per status, dan 10 presensi terbaru.
     *
     * @return array{rekap: Collection<int, array<string, mixed>>, total: Collection<string, int>, riwayat: Collection<int, Presensi>}
     */
    public function presensi(Siswa $siswa): array
    {
        $pengampu = $siswa->pengampu()->with('mapel')->get()->sortBy('mapel.nama_mapel')->values();

        $hitung = Presensi::where('siswa_id', $siswa->id)
            ->whereIn('pengampu_id', $pengampu->pluck('id'))
            ->selectRaw('pengampu_id, status, COUNT(*) as jumlah')
            ->groupBy('pengampu_id', 'status')
            ->get()
            ->groupBy('pengampu_id');

        $rekap = $pengampu->map(function (Pengampu $p) use ($hitung) {
            $baris = ['pengampu' => $p];
            foreach (array_keys(PresensiService::STATUS) as $st) {
                $baris[$st] = (int) ($hitung->get($p->id)?->firstWhere('status', $st)?->jumlah ?? 0);
            }
            $total = $baris['hadir'] + $baris['izin'] + $baris['sakit'] + $baris['alpa'];
            $baris['persen'] = $total ? round($baris['hadir'] / $total * 100, 1) : null;

            return $baris;
        });

        return [
            'rekap' => $rekap,
            'total' => collect(array_keys(PresensiService::STATUS))->mapWithKeys(fn ($st) => [$st => $rekap->sum($st)]),
            'riwayat' => Presensi::with('pengampu.mapel')
                ->where('siswa_id', $siswa->id)
                ->whereIn('pengampu_id', $pengampu->pluck('id'))
                ->latest('tanggal')
                ->latest('id')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * Nilai per mapel beserta peringkat kelas.
     *
     * @return array{pengampu: Collection<int, Pengampu>, nilai: Collection<int, Nilai>, kkm: float, peringkat: ?array<string, mixed>, jumlahSiswa: int}
     */
    public function nilai(Siswa $siswa): array
    {
        $kelas = $siswa->kelasPada();
        $pengampu = $siswa->pengampu()->with(['mapel', 'guru'])->get()->sortBy('mapel.nama_mapel')->values();

        return [
            'pengampu' => $pengampu,
            'nilai' => $siswa->nilai()->whereIn('pengampu_id', $pengampu->pluck('id'))->get()->keyBy('pengampu_id'),
            'kkm' => (float) pengaturan('kkm', 75),
            'peringkat' => $kelas ? $this->ranking->kelas($kelas)->firstWhere(fn ($r) => $r['siswa']->id === $siswa->id) : null,
            'jumlahSiswa' => $kelas?->siswa()->where('siswa.status', 'aktif')->count() ?? 0,
        ];
    }

    /**
     * Tugas beserta status pengumpulan siswa; yang masih harus dikerjakan di atas.
     *
     * @param  Collection<int, int>|null  $pengampuIds  default: semua mapel siswa
     * @return Collection<int, array{tugas: Tugas, pengumpulan: ?PengumpulanTugas, status: string}>
     */
    public function daftarTugas(Siswa $siswa, ?Collection $pengampuIds = null): Collection
    {
        $pengampuIds ??= $siswa->pengampu()->pluck('id');

        return Tugas::with(['pengampu.mapel', 'pengumpulan' => fn ($q) => $q->where('siswa_id', $siswa->id)])
            ->whereIn('pengampu_id', $pengampuIds)
            ->get()
            ->map(function (Tugas $t) {
                $p = $t->pengumpulan->first();

                return ['tugas' => $t, 'pengumpulan' => $p, 'status' => $t->statusSiswa($p)];
            })
            ->sortBy(fn ($b) => [$b['status'] === 'belum' ? 0 : 1, $b['status'] === 'belum' ? $b['tugas']->deadline->timestamp : -$b['tugas']->deadline->timestamp])
            ->values();
    }

    /**
     * Kuis beserta status pengerjaan siswa.
     *
     * @param  Collection<int, int>|null  $pengampuIds  default: semua mapel siswa
     * @return Collection<int, array{kuis: Kuis, jawaban: ?JawabanKuis, status: string}>
     */
    public function daftarKuis(Siswa $siswa, ?Collection $pengampuIds = null): Collection
    {
        $pengampuIds ??= $siswa->pengampu()->pluck('id');
        $urutanStatus = array_flip(array_keys(Kuis::STATUS_SISWA));

        return Kuis::with(['pengampu.mapel', 'jawaban' => fn ($q) => $q->where('siswa_id', $siswa->id)])
            ->withCount('soal')
            ->whereIn('pengampu_id', $pengampuIds)
            ->latest('waktu_mulai')
            ->get()
            ->map(function (Kuis $k) {
                $j = $k->jawaban->first()?->setRelation('kuis', $k);

                return ['kuis' => $k, 'jawaban' => $j, 'status' => $this->pengerjaanKuis->statusSiswa($k, $j)];
            })
            ->sortBy(fn ($b) => $urutanStatus[$b['status']])
            ->values();
    }
}
