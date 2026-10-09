<?php

namespace App\Policies;

use App\Models\SuratIzin;
use App\Models\User;

class SuratIzinPolicy
{
    /** Hanya wali kelas dari kelas surat izin yang boleh memproses. */
    public function proses(User $user, SuratIzin $suratIzin): bool
    {
        return $user->role === 'guru'
            && $user->guru !== null
            && $suratIzin->kelas?->wali_kelas_id === $user->guru->id;
    }
}
