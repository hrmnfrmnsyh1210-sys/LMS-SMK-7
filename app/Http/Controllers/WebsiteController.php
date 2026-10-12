<?php

namespace App\Http\Controllers;

use App\Exceptions\AiGagalException;
use App\Models\Berita;
use App\Models\Galeri;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kepsek;
use App\Models\Siswa;
use App\Services\AsistenAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Website profil sekolah untuk publik (tanpa login). */
class WebsiteController extends Controller
{
    public function beranda(): View
    {
        return view('web.beranda', [
            'statistik' => [
                'siswa' => Siswa::where('status', 'aktif')->count(),
                'guru' => Guru::whereHas('user', fn ($q) => $q->where('status', 'aktif'))->count(),
                'jurusan' => Jurusan::count(),
            ],
            'jurusan' => Jurusan::orderBy('kode')->get(),
            'berita' => Berita::terbit()->latest('diterbitkan_pada')->limit(3)->get(),
            'galeri' => Galeri::terurut()->limit(8)->get(),
            'namaKepsek' => Kepsek::value('nama'),
        ]);
    }

    public function berita(Request $request): View
    {
        $kategori = $request->string('kategori')->value();
        $kategori = array_key_exists($kategori, Berita::KATEGORI) ? $kategori : null;

        $berita = Berita::terbit()
            ->when($kategori, fn ($q) => $q->where('kategori', $kategori))
            ->latest('diterbitkan_pada')
            ->paginate(9)
            ->withQueryString();

        return view('web.berita.index', compact('berita', 'kategori'));
    }

    public function beritaShow(Berita $berita): View
    {
        abort_unless($berita->sudahTerbit(), 404);

        $lainnya = Berita::terbit()->whereKeyNot($berita->id)->latest('diterbitkan_pada')->limit(3)->get();

        return view('web.berita.show', compact('berita', 'lainnya'));
    }

    public function galeri(): View
    {
        return view('web.galeri', ['galeri' => Galeri::terurut()->paginate(24)]);
    }

    /** Chat asisten informasi sekolah untuk pengunjung. Riwayat hanya disimpan di browser. */
    public function tanyaAi(Request $request, AsistenAiService $ai): JsonResponse
    {
        $data = $request->validate([
            'pesan' => ['required', 'string', 'max:500'],
            'riwayat' => ['nullable', 'array', 'max:16'],
            'riwayat.*.role' => ['required', 'in:user,assistant'],
            'riwayat.*.content' => ['required', 'string', 'max:4000'],
        ]);

        try {
            $balasan = $ai->tanyaPengunjung($data['riwayat'] ?? [], $data['pesan']);
        } catch (AiGagalException $e) {
            return response()->json(['pesan' => $e->getMessage()], 503);
        }

        return response()->json(['balasan' => $balasan, 'html' => $ai->html($balasan)]);
    }
}
