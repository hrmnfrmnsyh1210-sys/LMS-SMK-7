<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Siswa\SuratIzinRequest;
use App\Models\SuratIzin;
use App\Services\SuratIzinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Pengajuan surat izin/sakit ke wali kelas. */
class SuratIzinController extends Controller
{
    public function index(Request $request): View
    {
        $siswa = $request->user()->siswa;

        return view('siswa.surat-izin', [
            'surat' => $siswa->suratIzin()->with('pemroses')->latest()->paginate(config('lms.per_page')),
            'kelas' => $siswa->kelasPada(),
        ]);
    }

    public function store(SuratIzinRequest $request, SuratIzinService $service): RedirectResponse
    {
        $surat = $service->ajukan($request->user()->siswa, $request->safe()->except('file'), $request->file('file'), $request->user());

        return $surat
            ? back()->with('success', 'Surat izin terkirim ke wali kelas.')
            : back()->with('error', 'Anda belum terdaftar di kelas pada tahun ajaran aktif.');
    }

    /** Surat yang belum diproses boleh dibatalkan. */
    public function destroy(Request $request, SuratIzin $suratIzin): RedirectResponse
    {
        abort_unless($suratIzin->siswa_id === $request->user()->siswa->id, 403);

        if ($suratIzin->status !== 'menunggu') {
            return back()->with('error', 'Surat yang sudah diproses tidak dapat dibatalkan.');
        }

        if ($suratIzin->file_path) {
            Storage::disk('public')->delete($suratIzin->file_path);
        }
        $suratIzin->delete();

        return back()->with('success', 'Surat izin dibatalkan.');
    }
}
