<?php

namespace App\Services;

use App\Models\Pengampu;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\SuratIzin;
use App\Notifications\Pemberitahuan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

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
    public function simpan(Pengampu $pengampu, int $pertemuan, string $tanggal, array $status): void
    {
        $siswaValid = $pengampu->daftarSiswa()->pluck('id')->all();
        $statusLama = Presensi::where('pengampu_id', $pengampu->id)->where('pertemuan_ke', $pertemuan)->pluck('status', 'siswa_id');

        DB::transaction(function () use ($pengampu, $pertemuan, $tanggal, $status, $siswaValid) {
            foreach ($status as $siswaId => $nilai) {
                if (! in_array((int) $siswaId, $siswaValid, true)) {
                    continue;
                }

                Presensi::updateOrCreate(
                    ['pengampu_id' => $pengampu->id, 'siswa_id' => $siswaId, 'pertemuan_ke' => $pertemuan],
                    ['tanggal' => $tanggal, 'status' => $nilai]
                );
            }

            // Tanggal pertemuan disamakan untuk semua siswa.
            Presensi::where('pengampu_id', $pengampu->id)->where('pertemuan_ke', $pertemuan)->update(['tanggal' => $tanggal]);
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
            ->selectRaw('pertemuan_ke, MIN(tanggal) as tanggal')
            ->selectRaw("SUM(status = 'hadir') as hadir, SUM(status = 'izin') as izin, SUM(status = 'sakit') as sakit, SUM(status = 'alpa') as alpa")
            ->groupBy('pertemuan_ke')
            ->orderBy('pertemuan_ke')
            ->get();
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
