<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\AkunService;
use Illuminate\Http\RedirectResponse;

/** Aksi akun yang berlaku untuk kepsek, guru, dan siswa. */
class AkunController extends Controller
{
    public function __construct(private AkunService $akun) {}

    public function resetPassword(User $user): RedirectResponse
    {
        $this->pastikanBukanOperator($user);

        $password = $this->akun->resetPassword($user);
        ActivityLogger::log('reset_password', "Reset password akun {$user->username} ({$user->label_role})");

        return back()->with('success', "Password {$user->nama} direset ke default: {$password}. Pengguna wajib menggantinya saat login.");
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->pastikanBukanOperator($user);

        $user->update(['status' => $user->isAktif() ? 'nonaktif' : 'aktif']);
        ActivityLogger::log('ubah_status_akun', "Akun {$user->username} di-{$user->status}-kan");

        return back()->with('success', "Akun {$user->nama} sekarang {$user->status}.");
    }

    private function pastikanBukanOperator(User $user): void
    {
        abort_if($user->role === 'operator', 403, 'Akun operator tidak dapat diubah dari sini.');
    }
}
