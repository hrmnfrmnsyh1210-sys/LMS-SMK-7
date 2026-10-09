<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\PengaturanRequest;
use App\Models\Pengaturan;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Profil sekolah, logo, dan kebijakan (password & KKM). */
class PengaturanController extends Controller
{
    public function edit(): View
    {
        return view('operator.pengaturan.edit', ['pengaturan' => Pengaturan::semua()]);
    }

    public function update(PengaturanRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            $lama = Pengaturan::ambil('logo');
            $data['logo'] = $request->file('logo')->store('sekolah', 'public');
            if ($lama) {
                Storage::disk('public')->delete($lama);
            }
        }

        foreach ($data as $kunci => $nilai) {
            Pengaturan::simpan($kunci, $nilai);
        }

        ActivityLogger::log('ubah_pengaturan', 'Memperbarui pengaturan sistem');

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
