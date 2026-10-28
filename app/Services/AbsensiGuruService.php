<?php

namespace App\Services;

use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\Jadwal;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Absensi guru per sesi mengajar: guru absen masuk saat memasuki kelas sesuai jadwal
 * dan absen keluar saat selesai. Rekapnya dipantau kepala sekolah & operator.
 */
class AbsensiGuruService
{
    /** Absen masuk dibuka sekian menit sebelum jam pelajaran dimulai. */
    public const BUKA_SEBELUM_MENIT = 15;

    /** Toleransi keterlambatan (menit) setelah jam mulai sebelum ditandai terlambat. */
    public function toleransi(): int
    {
        return (int) pengaturan('toleransi_terlambat_guru', 10);
    }

    /**
     * Jadwal mengajar guru hari ini beserta absensinya.
     *
     * @return Collection<int, Jadwal>
     */
    public function jadwalHariIni(Guru $guru): Collection
    {
        $hari = Jadwal::hariDari(today());

        if (! $hari) {
            return collect();
        }

        return $this->queryJadwal()
            ->where('hari', $hari)
            ->whereHas('pengampu', fn ($q) => $q->where('guru_id', $guru->id))
            ->with(['absensiGuru' => fn ($q) => $q->whereDate('tanggal', today())])
            ->get();
    }

    public function masuk(Guru $guru, Jadwal $jadwal, ?string $ip = null): AbsensiGuru
    {
        $this->pastikanJadwalHariIni($guru, $jadwal);

        if ($this->absensiHariIni($jadwal)) {
            throw ValidationException::withMessages(['absensi' => 'Anda sudah absen masuk untuk kelas ini.']);
        }

        $mulai = $this->waktu($jadwal->jam_mulai);
        if (now()->lt($mulai->copy()->subMinutes(self::BUKA_SEBELUM_MENIT))) {
            throw ValidationException::withMessages(['absensi' => 'Absen masuk baru dibuka '.self::BUKA_SEBELUM_MENIT.' menit sebelum pelajaran dimulai.']);
        }
        if (now()->gt($this->waktu($jadwal->jam_selesai))) {
            throw ValidationException::withMessages(['absensi' => 'Jam pelajaran ini sudah berakhir.']);
        }

        $jam = now()->format('H:i:s');
        $absensi = AbsensiGuru::create([
            'guru_id' => $guru->id,
            'jadwal_id' => $jadwal->id,
            'tanggal' => today(),
            'jam_masuk' => $jam,
            'status' => now()->gt($mulai->copy()->addMinutes($this->toleransi())) ? 'terlambat' : 'hadir',
            'ip_masuk' => $ip,
        ]);

        ActivityLogger::log('absen_masuk_kelas', "Masuk kelas {$jadwal->pengampu->kelas->nama_kelas} ({$jadwal->pengampu->mapel->nama_mapel}) pukul {$jam}");

        return $absensi;
    }

    public function keluar(Guru $guru, Jadwal $jadwal, ?string $ip = null): AbsensiGuru
    {
        $this->pastikanJadwalHariIni($guru, $jadwal);
        $absensi = $this->absensiHariIni($jadwal);

        if (! $absensi) {
            throw ValidationException::withMessages(['absensi' => 'Lakukan absen masuk kelas terlebih dahulu.']);
        }
        if ($absensi->jam_keluar) {
            throw ValidationException::withMessages(['absensi' => 'Anda sudah absen keluar dari kelas ini.']);
        }

        $absensi->update(['jam_keluar' => now()->format('H:i:s'), 'ip_keluar' => $ip]);

        ActivityLogger::log('absen_keluar_kelas', "Keluar kelas {$jadwal->pengampu->kelas->nama_kelas} ({$jadwal->pengampu->mapel->nama_mapel}) pukul {$absensi->jam_keluar}");

        return $absensi;
    }

    /**
     * Seluruh sesi mengajar pada satu tanggal beserta status kehadiran gurunya.
     * Status: hadir, terlambat, belum (sesi belum berakhir), tidak_hadir.
     *
     * @return Collection<int, array{jadwal: Jadwal, absensi: ?AbsensiGuru, status: string}>
     */
    public function rekapHarian(CarbonInterface $tanggal, ?string $cari = null): Collection
    {
        $hari = Jadwal::hariDari($tanggal);

        if (! $hari) {
            return collect();
        }

        $absensi = AbsensiGuru::whereDate('tanggal', $tanggal)->get()->keyBy('jadwal_id');

        return $this->queryJadwal($cari)
            ->where('hari', $hari)
            ->get()
            ->map(function (Jadwal $j) use ($absensi, $tanggal) {
                $a = $absensi->get($j->id);
                $berakhir = Carbon::parse($tanggal->toDateString().' '.$j->jam_selesai)->isPast();

                return ['jadwal' => $j, 'absensi' => $a, 'status' => $a?->status ?? ($berakhir ? 'tidak_hadir' : 'belum')];
            });
    }

    /**
     * Rekap sesi mengajar tiap guru dalam satu bulan (sesi dihitung dari jadwal yang berlaku saat ini).
     *
     * @return Collection<int, array{guru: Guru, terjadwal: int, hadir: int, tepat_waktu: int, terlambat: int, tidak_hadir: int}>
     */
    public function rekapBulanan(CarbonInterface $bulan, ?string $cari = null): Collection
    {
        $awal = $bulan->copy()->startOfMonth();
        $akhir = $bulan->copy()->endOfMonth();

        $jadwalPerGuru = $this->queryJadwal()->get()->groupBy('pengampu.guru_id');
        $absensi = AbsensiGuru::whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])->get()->groupBy('guru_id');

        // Sesi yang sudah berakhir dalam bulan itu, dihitung per hari jadwal.
        $terjadwal = function (Collection $jadwal) use ($awal, $akhir): int {
            $jumlah = 0;
            for ($tgl = $awal->copy(); $tgl->lte($akhir) && $tgl->lte(today()); $tgl->addDay()) {
                $hari = Jadwal::hariDari($tgl);
                $jumlah += $jadwal->filter(fn (Jadwal $j) => $j->hari === $hari
                    && Carbon::parse($tgl->toDateString().' '.$j->jam_selesai)->isPast())->count();
            }

            return $jumlah;
        };

        return Guru::query()
            ->when($cari, fn ($q) => $q->where(fn ($q) => $q->where('nama', 'like', "%{$cari}%")->orWhere('nip', 'like', "%{$cari}%")))
            ->orderBy('nama')
            ->get()
            ->map(function (Guru $g) use ($jadwalPerGuru, $absensi, $terjadwal) {
                $milik = $absensi->get($g->id, collect());
                $sesi = $terjadwal($jadwalPerGuru->get($g->id, collect()));

                return [
                    'guru' => $g,
                    'terjadwal' => $sesi,
                    'hadir' => $milik->count(),
                    'tepat_waktu' => $milik->where('status', 'hadir')->count(),
                    'terlambat' => $milik->where('status', 'terlambat')->count(),
                    'tidak_hadir' => max(0, $sesi - $milik->count()),
                ];
            });
    }

    /** @return Builder<Jadwal> */
    private function queryJadwal(?string $cari = null): Builder
    {
        return Jadwal::query()
            ->with(['pengampu.guru', 'pengampu.kelas', 'pengampu.mapel'])
            ->whereHas('pengampu', fn ($q) => $q->tahunAktif()
                ->when($cari, fn ($q) => $q->whereHas('guru', fn ($q) => $q->where('nama', 'like', "%{$cari}%")->orWhere('nip', 'like', "%{$cari}%"))))
            ->orderBy('jam_mulai');
    }

    private function pastikanJadwalHariIni(Guru $guru, Jadwal $jadwal): void
    {
        abort_unless($jadwal->pengampu->guru_id === $guru->id, 403, 'Jadwal ini bukan milik Anda.');

        if ($jadwal->hari !== Jadwal::hariDari(today())) {
            throw ValidationException::withMessages(['absensi' => 'Jadwal ini bukan untuk hari ini.']);
        }
    }

    private function absensiHariIni(Jadwal $jadwal): ?AbsensiGuru
    {
        return $jadwal->absensiGuru()->whereDate('tanggal', today())->first();
    }

    private function waktu(string $jam): Carbon
    {
        return Carbon::parse(today()->toDateString().' '.$jam);
    }
}
