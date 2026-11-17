<?php

namespace App\Services;

use App\Models\AbsensiGuru;
use App\Models\Jadwal;
use App\Models\Pengampu;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\SuratIzin;
use App\Notifications\Pemberitahuan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class PresensiService
{
    public const STATUS = ['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'];

    /**
     * Status awal form presensi: data tersimpan bila ada, jika tidak "hadir",
     * kecuali siswa memiliki surat izin disetujui pada tanggal tersebut.
     *
     * @param  Collection<int, Siswa>  $siswa
     * @return array<int, string> siswa_id => status
     */
    public function statusAwal(Pengampu $pengampu, int $pertemuan, string $tanggal, Collection $siswa): array
    {
        $tersimpan = Presensi::where('pengampu_id', $pengampu->id)
            ->where('pertemuan_ke', $pertemuan)
            ->pluck('status', 'siswa_id');

        $izin = $this->izinDisetujui($siswa->pluck('id')->all(), $tanggal);

        return $siswa->mapWithKeys(fn (Siswa $s) => [
            $s->id => $tersimpan[$s->id] ?? $izin[$s->id] ?? 'hadir',
        ])->all();
    }

    /**
     * Simpan presensi satu pertemuan. Orang tua siswa yang baru tercatat alpa diberi notifikasi.
     *
     * @param  array<int|string, string>  $status  siswa_id => status
     */
    public function simpan(Pengampu $pengampu, int $pertemuan, string $tanggal, array $status, ?string $catatan = null, ?string $materi = null, ?string $tugas = null, ?string $dokumentasi = null): void
    {
        $siswaValid = $pengampu->daftarSiswa()->pluck('id')->all();
        $statusLama = Presensi::where('pengampu_id', $pengampu->id)->where('pertemuan_ke', $pertemuan)->pluck('status', 'siswa_id');

        DB::transaction(function () use ($pengampu, $pertemuan, $tanggal, $status, $siswaValid, $catatan, $materi, $tugas, $dokumentasi) {
            foreach ($status as $siswaId => $nilai) {
                if (! in_array((int) $siswaId, $siswaValid, true)) {
                    continue;
                }

                Presensi::updateOrCreate(
                    ['pengampu_id' => $pengampu->id, 'siswa_id' => $siswaId, 'pertemuan_ke' => $pertemuan],
                    ['tanggal' => $tanggal, 'status' => $nilai]
                );
            }

            // Tanggal, catatan, materi, tugas, dan bukti dokumentasi pertemuan disamakan untuk semua siswa.
            $updateData = [
                'tanggal' => $tanggal,
                'catatan' => $catatan,
                'materi' => $materi,
                'tugas' => $tugas,
            ];

            if ($dokumentasi !== null) {
                $updateData['dokumentasi'] = $dokumentasi ?: null;
            }

            Presensi::where('pengampu_id', $pengampu->id)->where('pertemuan_ke', $pertemuan)->update($updateData);

            // Sinkronkan ke jurnal mengajar guru (AbsensiGuru) jika ada jadwal pada hari dan tanggal tersebut.
            if ($materi || $tugas || $dokumentasi) {
                $hari = Jadwal::hariDari(Carbon::parse($tanggal));
                if ($hari) {
                    $jadwalIds = $pengampu->jadwal()->where('hari', $hari)->pluck('id');
                    if ($jadwalIds->isNotEmpty()) {
                        $updateAbsensi = array_filter([
                            'materi' => $materi,
                            'aktivitas' => $tugas,
                            'dokumentasi' => $dokumentasi ?: null,
                        ], fn ($v) => $v !== null);

                        if ($updateAbsensi) {
                            AbsensiGuru::where('guru_id', $pengampu->guru_id)
                                ->whereIn('jadwal_id', $jadwalIds)
                                ->whereDate('tanggal', $tanggal)
                                ->update($updateAbsensi);
                        }
                    }
                }
            }
        });

        $baruAlpa = collect($status)
            ->filter(fn ($nilai, $siswaId) => $nilai === 'alpa' && ($statusLama[$siswaId] ?? null) !== 'alpa'
                && in_array((int) $siswaId, $siswaValid, true))
            ->keys();

        $this->beriTahuOrtuAlpa($pengampu, $pertemuan, $tanggal, $baruAlpa->map(fn ($id) => (int) $id)->all());
    }

    /** @param list<int> $siswaIds */
    private function beriTahuOrtuAlpa(Pengampu $pengampu, int $pertemuan, string $tanggal, array $siswaIds): void
    {
        if ($siswaIds === []) {
            return;
        }

        $pengampu->loadMissing('mapel');
        Siswa::with('orangTua.user')->whereIn('id', $siswaIds)->get()->each(function (Siswa $siswa) use ($pengampu, $pertemuan, $tanggal) {
            Notification::send(
                $siswa->orangTua->pluck('user')->filter(),
                new Pemberitahuan(
                    "{$siswa->nama} tercatat alpa pada {$pengampu->mapel->nama_mapel} pertemuan {$pertemuan} (".tanggal_indo($tanggal, false).').',
                    route('ortu.presensi'),
                ),
            );
        });
    }

    /**
     * Ringkasan per pertemuan: [pertemuan_ke, tanggal, hadir, izin, sakit, alpa].
     *
     * @return Collection<int, object>
     */
    public function daftarPertemuan(Pengampu $pengampu): Collection
    {
        return Presensi::where('pengampu_id', $pengampu->id)
            ->selectRaw('pertemuan_ke, MIN(tanggal) as tanggal, MAX(catatan) as catatan, MAX(materi) as materi, MAX(tugas) as tugas, MAX(dokumentasi) as dokumentasi')
            ->selectRaw("SUM(status = 'hadir') as hadir, SUM(status = 'izin') as izin, SUM(status = 'sakit') as sakit, SUM(status = 'alpa') as alpa")
            ->groupBy('pertemuan_ke')
            ->orderBy('pertemuan_ke')
            ->get();
    }

    /**
     * Memeriksa apakah sesi presensi untuk pengampu hari ini sudah dibuka oleh guru.
     * Sesi terbuka bila guru sudah absen masuk kelas pada jadwal hari ini (dan belum keluar),
     * atau bila guru sudah mengisi/membuka data presensi hari ini.
     */
    public function isSesiPresensiTerbuka(Pengampu $pengampu, ?string $tanggal = null): bool
    {
        $tanggal = $tanggal ?? today()->toDateString();

        $absensiGuru = AbsensiGuru::whereHas('jadwal', fn ($q) => $q->where('pengampu_id', $pengampu->id))
            ->whereDate('tanggal', $tanggal)
            ->latest('jam_masuk')
            ->first();

        if ($absensiGuru) {
            return $absensiGuru->jam_masuk !== null && $absensiGuru->jam_keluar === null;
        }

        return Presensi::where('pengampu_id', $pengampu->id)
            ->whereDate('tanggal', $tanggal)
            ->exists();
    }

    /**
     * Mengembalikan koleksi ID pengampu yang sesi presensinya sedang terbuka hari ini.
     *
     * @param  iterable<int>  $pengampuIds
     * @return Collection<int, int>
     */
    public function daftarSesiTerbuka(iterable $pengampuIds, ?string $tanggal = null): Collection
    {
        $tanggal = $tanggal ?? today()->toDateString();

        $terbuka = collect();
        foreach ($pengampuIds as $id) {
            $absensiGuru = AbsensiGuru::whereHas('jadwal', fn ($q) => $q->where('pengampu_id', $id))
                ->whereDate('tanggal', $tanggal)
                ->latest('jam_masuk')
                ->first();

            if ($absensiGuru) {
                if ($absensiGuru->jam_masuk !== null && $absensiGuru->jam_keluar === null) {
                    $terbuka->push((int) $id);
                }
            } elseif (Presensi::where('pengampu_id', $id)->whereDate('tanggal', $tanggal)->exists()) {
                $terbuka->push((int) $id);
            }
        }

        return $terbuka->unique()->values();
    }

    public function absenMandiri(Siswa $siswa, Pengampu $pengampu, string $status = 'hadir', ?string $catatan = null): Presensi
    {
        abort_unless($pengampu->kelas_id === $siswa->kelasPada()?->id, 403, 'Anda bukan siswa di kelas ini.');

        if (! in_array($status, ['hadir', 'izin', 'sakit'], true)) {
            throw ValidationException::withMessages(['status' => 'Status presensi tidak valid.']);
        }

        if (! $this->isSesiPresensiTerbuka($pengampu)) {
            throw ValidationException::withMessages([
                'presensi' => 'Presensi belum dibuka oleh guru. Anda baru dapat presensi setelah guru melakukan absen masuk kelas.',
            ]);
        }

        $sudah = Presensi::where('pengampu_id', $pengampu->id)
            ->where('siswa_id', $siswa->id)
            ->whereDate('tanggal', today())
            ->first();

        if ($sudah) {
            throw ValidationException::withMessages(['presensi' => 'Anda sudah melakukan presensi untuk mata pelajaran ini hari ini.']);
        }

        $pertemuan = Presensi::where('pengampu_id', $pengampu->id)
            ->whereDate('tanggal', today())
            ->value('pertemuan_ke');

        if (! $pertemuan) {
            $pertemuanMax = (int) Presensi::where('pengampu_id', $pengampu->id)->max('pertemuan_ke');
            $pertemuan = $pertemuanMax + 1;
        }

        $presensi = Presensi::create([
            'pengampu_id' => $pengampu->id,
            'siswa_id' => $siswa->id,
            'pertemuan_ke' => $pertemuan,
            'tanggal' => today(),
            'status' => $status,
            'catatan' => $catatan,
            'diabsen_oleh' => 'siswa',
            'waktu_absen' => now(),
        ]);

        ActivityLogger::log('presensi_mandiri', "Siswa {$siswa->nama} melakukan presensi mandiri ({$status}) pertemuan {$pertemuan} ({$pengampu->label})");

        return $presensi;
    }

    /**
     * Rekap per siswa untuk satu pengampu.
     *
     * @return Collection<int, array{siswa: Siswa, hadir: int, izin: int, sakit: int, alpa: int, persen: ?float}>
     */
    public function rekap(Pengampu $pengampu): Collection
    {
        $hitung = Presensi::where('pengampu_id', $pengampu->id)
            ->selectRaw('siswa_id, status, COUNT(*) as jumlah')
            ->groupBy('siswa_id', 'status')
            ->get()
            ->groupBy('siswa_id');

        return $pengampu->daftarSiswa()->map(function (Siswa $s) use ($hitung) {
            $baris = ['siswa' => $s];
            foreach (array_keys(self::STATUS) as $st) {
                $baris[$st] = (int) ($hitung->get($s->id)?->firstWhere('status', $st)?->jumlah ?? 0);
            }
            $total = $baris['hadir'] + $baris['izin'] + $baris['sakit'] + $baris['alpa'];
            $baris['persen'] = $total ? round($baris['hadir'] / $total * 100, 1) : null;

            return $baris;
        });
    }

    /**
     * Surat izin disetujui yang mencakup tanggal tertentu.
     *
     * @param  list<int>  $siswaIds
     * @return array<int, string> siswa_id => 'izin'|'sakit'
     */
    public function izinDisetujui(array $siswaIds, string $tanggal): array
    {
        $tgl = Carbon::parse($tanggal)->toDateString();

        return SuratIzin::whereIn('siswa_id', $siswaIds)
            ->where('status', 'disetujui')
            ->whereDate('tanggal_mulai', '<=', $tgl)
            ->whereDate('tanggal_selesai', '>=', $tgl)
            ->pluck('jenis', 'siswa_id')
            ->all();
    }
}
