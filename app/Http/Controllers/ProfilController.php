<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilPasswordRequest;
use App\Http\Requests\ProfilUpdateRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Profil akun — dipakai bersama oleh semua role. */
class ProfilController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profil.edit', ['user' => $user, 'profil' => $user->profil()]);
    }

    public function update(ProfilUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            $user->update(['email' => $data['email'] ?? null]);
            $user->profil()?->update(Arr::except($data, ['email']));
        });

        ActivityLogger::log('ubah_profil', 'Memperbarui profil akun');

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function password(): View
    {
        return view('profil.password');
    }

    public function updatePassword(ProfilPasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->validated('password'),
            'wajib_ganti_password' => false,
        ]);

        ActivityLogger::log('ganti_password', 'Mengganti password akun');

        return redirect()->route('dashboard')->with('success', 'Password berhasil diganti.');
    }
}
