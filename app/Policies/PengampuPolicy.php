<?php

namespace App\Policies;

use App\Models\Pengampu;
use App\Models\User;

class PengampuPolicy
{
    /** Guru hanya boleh mengelola pembelajaran pada pengampu miliknya. */
    public function kelola(User $user, Pengampu $pengampu): bool
    {
        return $user->role === 'guru' && $user->guru?->id === $pengampu->guru_id;
    }

    /** Siswa hanya boleh mengakses pembelajaran kelas-mapel tempat ia terdaftar. */
    public function ikuti(User $user, Pengampu $pengampu): bool
    {
        return $user->role === 'siswa'
            && $user->siswa?->status === 'aktif'
            && $user->siswa->mengikuti($pengampu);
    }
}
