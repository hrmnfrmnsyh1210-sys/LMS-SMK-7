<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\GaleriRequest;
use App\Models\Galeri;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GaleriController extends Controller
{
    public function index(): View
    {
        $galeri = Galeri::terurut()->paginate(24);

        return view('operator.website.galeri.index', compact('galeri'));
    }

    public function create(): View
    {
        return view('operator.website.galeri.form', ['galeri' => new Galeri]);
    }

    public function store(GaleriRequest $request): RedirectResponse
    {
        $galeri = Galeri::create([
            ...$request->safe()->only(['judul', 'keterangan']),
            'urutan' => (int) $request->validated('urutan'),
            'gambar' => $request->file('gambar')->store('galeri', 'public'),
        ]);
        ActivityLogger::log('tambah_galeri', "Menambah foto galeri {$galeri->judul}");

        return redirect()->route('operator.galeri.index')->with('success', 'Foto berhasil ditambahkan.');
    }

    public function edit(Galeri $galeri): View
    {
        return view('operator.website.galeri.form', compact('galeri'));
    }

    public function update(GaleriRequest $request, Galeri $galeri): RedirectResponse
    {
        $data = [...$request->safe()->only(['judul', 'keterangan']), 'urutan' => (int) $request->validated('urutan')];

        if ($request->hasFile('gambar')) {
            Storage::disk('public')->delete($galeri->gambar);
            $data['gambar'] = $request->file('gambar')->store('galeri', 'public');
        }

        $galeri->update($data);
        ActivityLogger::log('ubah_galeri', "Mengubah foto galeri {$galeri->judul}");

        return redirect()->route('operator.galeri.index')->with('success', 'Foto berhasil diperbarui.');
    }

    public function destroy(Galeri $galeri): RedirectResponse
    {
        Storage::disk('public')->delete($galeri->gambar);

        $judul = $galeri->judul;
        $galeri->delete();
        ActivityLogger::log('hapus_galeri', "Menghapus foto galeri {$judul}");

        return back()->with('success', 'Foto berhasil dihapus.');
    }
}
