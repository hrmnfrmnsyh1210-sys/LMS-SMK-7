<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Pengampu;

class JadwalService
{
    /**
     * Cari jadwal yang beririsan pada hari yang sama untuk kelas yang sama
     * atau guru yang sama. Mengembalikan pesan bentrok, atau null jika aman.
     */
    public function cekBentrok(Pengampu $pengampu, string $hari, string $mulai, string $selesai, ?int $kecualiId = null): ?string
    {
        $bentrok = Jadwal::query()
            ->with(['pengampu.kelas', 'pengampu.guru', 'pengampu.mapel'])
            ->where('hari', $hari)
            ->where('jam_mulai', '<', $selesai)
            ->where('jam_selesai', '>', $mulai)
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
            ->whereHas('pengampu', fn ($q) => $q
                ->where('tahun_ajaran_id', $pengampu->tahun_ajaran_id)
                ->where(fn ($q) => $q
                    ->where('kelas_id', $pengampu->kelas_id)
                    ->orWhere('guru_id', $pengampu->guru_id)))
            ->first();

        if (! $bentrok) {
            return null;
        }

        $p = $bentrok->pengampu;
        $alasan = $p->kelas_id === $pengampu->kelas_id
            ? "kelas {$p->kelas->nama_kelas} sudah ada jadwal {$p->mapel->nama_mapel}"
            : "guru {$p->guru->nama} sudah mengajar {$p->mapel->nama_mapel} di {$p->kelas->nama_kelas}";

        return "Jadwal bentrok: {$alasan} pada {$bentrok->hari} {$bentrok->jam}.";
    }
}
