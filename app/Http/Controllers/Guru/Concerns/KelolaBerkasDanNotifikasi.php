<?php

namespace App\Http\Controllers\Guru\Concerns;

use App\Models\Pengampu;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

trait KelolaBerkasDanNotifikasi
{
    /**
     * Simpan file baru (mengganti yang lama) atau hapus bila diminta.
     * Mengembalikan path yang harus disimpan ke kolom file_path.
     */
    protected function prosesBerkas(Request $request, ?string $pathLama, string $folder): ?string
    {
        if ($request->hasFile('file')) {
            $this->hapusBerkas($pathLama);

            return $request->file('file')->store($folder, 'public');
        }

        if ($request->boolean('hapus_file')) {
            $this->hapusBerkas($pathLama);

            return null;
        }

        return $pathLama;
    }

    protected function hapusBerkas(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    /** Kirim notifikasi dashboard ke semua siswa di kelas pengampu. */
    protected function beriTahuSiswa(Pengampu $pengampu, string $pesan): void
    {
        $users = User::whereIn('id', $pengampu->daftarSiswa()->pluck('user_id'))->get();

        Notification::send($users, new Pemberitahuan($pesan));
    }
}
