<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\KontenWebsiteRequest;
use App\Models\Pengaturan;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Konten halaman depan website: hero, sambutan, visi-misi, PPDB, kontak & media sosial. */
class KontenWebsiteController extends Controller
{
    public function edit(): View
    {
        return view('operator.website.konten', ['konten' => Pengaturan::semua()]);
    }

    public function update(KontenWebsiteRequest $request): RedirectResponse
    {
        $data = $request->safe()->except([...KontenWebsiteRequest::GAMBAR, 'hapus_gambar']);
        $hapus = $request->validated('hapus_gambar', []);

        foreach (KontenWebsiteRequest::GAMBAR as $kunci) {
            $lama = Pengaturan::ambil($kunci);

            if ($request->hasFile($kunci)) {
                $data[$kunci] = $request->file($kunci)->store('website', 'public');
            } elseif (in_array($kunci, $hapus, true)) {
                $data[$kunci] = '';
            } else {
                continue;
            }

            if ($lama) {
                Storage::disk('public')->delete($lama);
            }
        }

        foreach ($data as $kunci => $nilai) {
            Pengaturan::simpan($kunci, $nilai ?? '');
        }

        ActivityLogger::log('ubah_konten_website', 'Memperbarui konten website sekolah');

        return back()->with('success', 'Konten website berhasil disimpan.');
    }
}
