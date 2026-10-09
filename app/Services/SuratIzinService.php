<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\SuratIzin;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SuratIzinService
{
    /**
     * Ajukan surat izin/sakit untuk siswa (oleh siswa sendiri atau orang tuanya) ke wali kelas aktif.
     * Mengembalikan null bila siswa belum terdaftar di kelas pada tahun ajaran aktif.
     *
     * @param  array{jenis: string, tanggal_mulai: string, tanggal_selesai: string, keterangan: string}  $data
     */
    public function ajukan(Siswa $siswa, array $data, ?UploadedFile $bukti, User $pengaju): ?SuratIzin
    {
        $kelas = $siswa->kelasPada();
        if (! $kelas) {
            return null;
        }

        $surat = $siswa->suratIzin()->create($data + [
            'kelas_id' => $kelas->id,
            'pengaju_user_id' => $pengaju->id,
            'file_path' => $bukti?->store('surat-izin', 'public'),
        ]);

        $oleh = $pengaju->role === 'ortu' ? " (diajukan orang tua: {$pengaju->nama})" : '';
        $kelas->waliKelas?->user?->notify(new Pemberitahuan(
            "Surat {$surat->jenis} baru dari {$siswa->nama} ({$kelas->nama_kelas}){$oleh}.",
            route('guru.wali.surat-izin'),
        ));
        ActivityLogger::log('ajukan_surat_izin', "Mengajukan surat {$surat->jenis} {$siswa->nama} ".tanggal_indo($surat->tanggal_mulai, false));

        return $surat;
    }

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

        // Siswa dan orang tuanya sama-sama diberi tahu.
        $penerima = User::whereKey($surat->siswa->user_id)
            ->orWhereIn('id', $surat->siswa->orangTua()->select('orang_tua.user_id'))
            ->get();
        Notification::send($penerima, new Pemberitahuan(
            "Surat {$surat->jenis} {$surat->siswa->nama} tanggal ".tanggal_indo($surat->tanggal_mulai, false)." {$status}.",
        ));

        return $diperbarui;
    }
}
