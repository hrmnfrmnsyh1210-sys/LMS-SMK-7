<?php

namespace App\Services;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /** Catat aksi penting ke tabel log_aktivitas. */
    public static function log(string $aksi, ?string $keterangan = null, ?User $user = null): LogAktivitas
    {
        return LogAktivitas::create([
            'user_id' => $user?->id ?? Auth::id(),
            'aksi' => $aksi,
            'keterangan' => $keterangan,
            'ip_address' => request()?->ip(),
        ]);
    }
}
