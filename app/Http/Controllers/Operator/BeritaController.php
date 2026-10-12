<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\BeritaRequest;
use App\Models\Berita;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BeritaController extends Controller
{
    public function index(Request $request): View
    {
        $cari = $request->string('q')->trim()->value();
        $kategori = $request->string('kategori')->value();

        $berita = Berita::query()
            ->when($cari, fn ($q) => $q->where('judul', 'like', "%{$cari}%"))
            ->when(array_key_exists($kategori, Berita::KATEGORI), fn ($q) => $q->where('kategori', $kategori))
            ->orderByRaw('diterbitkan_pada is null desc')
            ->latest('diterbitkan_pada')
            ->latest('id')
            ->paginate(config('lms.per_page'))
            ->withQueryString();

        return view('operator.website.berita.index', compact('berita', 'cari', 'kategori'));
    }

    public function create(): View
    {
        return view('operator.website.berita.form', ['berita' => new Berita(['kategori' => 'berita'])]);
    }

    public function store(BeritaRequest $request): RedirectResponse
    {
        $berita = Berita::create([
            ...$this->dataBerita($request),
            'penulis_user_id' => $request->user()->id,
            'gambar' => $request->file('gambar')?->store('berita', 'public'),
        ]);
        ActivityLogger::log('tambah_berita', "Menambah berita {$berita->judul}");

        return redirect()->route('operator.berita.index')->with('success', 'Berita berhasil disimpan.');
    }

    public function edit(Berita $berita): View
    {
        return view('operator.website.berita.form', compact('berita'));
    }

    public function update(BeritaRequest $request, Berita $berita): RedirectResponse
    {
        $data = $this->dataBerita($request, $berita);

        if ($request->hasFile('gambar') || $request->boolean('hapus_gambar')) {
            if ($berita->gambar) {
                Storage::disk('public')->delete($berita->gambar);
            }
            $data['gambar'] = $request->file('gambar')?->store('berita', 'public');
        }

        $berita->update($data);
        ActivityLogger::log('ubah_berita', "Mengubah berita {$berita->judul}");

        return redirect()->route('operator.berita.index')->with('success', 'Berita berhasil diperbarui.');
    }

    public function destroy(Berita $berita): RedirectResponse
    {
        if ($berita->gambar) {
            Storage::disk('public')->delete($berita->gambar);
        }

        $judul = $berita->judul;
        $berita->delete();
        ActivityLogger::log('hapus_berita', "Menghapus berita {$judul}");

        return back()->with('success', 'Berita berhasil dihapus.');
    }

    /**
     * Kolom teks berita. Tanpa centang "terbitkan" berita disimpan sebagai draf;
     * bila dicentang tanpa tanggal, tanggal terbit lama dipertahankan atau diisi sekarang.
     *
     * @return array{kategori: string, judul: string, ringkasan: ?string, isi: string, diterbitkan_pada: mixed}
     */
    private function dataBerita(BeritaRequest $request, ?Berita $berita = null): array
    {
        $terbit = null;
        if ($request->boolean('terbitkan')) {
            $terbit = $request->validated('diterbitkan_pada') ?? $berita?->diterbitkan_pada ?? now();
        }

        return [
            ...$request->safe()->only(['kategori', 'judul', 'ringkasan', 'isi']),
            'diterbitkan_pada' => $terbit,
        ];
    }
}
