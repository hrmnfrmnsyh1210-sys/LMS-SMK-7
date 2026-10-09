<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Presensi;
use App\Models\SuratIzin;
use App\Notifications\Pemberitahuan;
use Illuminate\Support\Facades\DB;

class SuratIzinService
{
    /**
     * Setujui/tolak surat izin. Jika disetujui, presensi yang sudah tercatat
     * pada rentang tanggal tersebut otomatis menjadi izin/sakit.
     *
     * @return int jumlah presensi yang diperbarui
     */
    public function proses(SuratIzin $surat, string $status, ?string $catatan, Guru $wali): int
    {
        $diperbarui = DB::transaction(function () use ($surat, $status, $catatan, $wali) {
            $surat->update(['status' => $status, 'catatan' => $catatan, 'diproses_oleh' => $wali->id]);

            if ($status !== 'disetujui') {
                return 0;
            }

            return Presensi::where('siswa_id', $surat->siswa_id)
                ->whereHas('pengampu', fn ($q) => $q->where('kelas_id', $surat->kelas_id))
                ->whereDate('tanggal', '>=', $surat->tanggal_mulai)
                ->whereDate('tanggal', '<=', $surat->tanggal_selesai)
                ->update(['status' => $surat->jenis]);
        });

        $surat->siswa->user?->notify(new Pemberitahuan(
            "Surat {$surat->jenis} tanggal ".tanggal_indo($surat->tanggal_mulai, false)." {$status}.",
        ));

        return $diperbarui;
    }
}
