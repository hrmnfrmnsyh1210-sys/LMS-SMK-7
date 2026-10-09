<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Ortu\Concerns\AnakDipantau;
use App\Http\Requests\Ortu\SuratIzinRequest;
use App\Models\Siswa;
use App\Models\SuratIzin;
use App\Services\SuratIzinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Orang tua mengajukan surat izin/sakit untuk anaknya dan memantau statusnya. */
class SuratIzinController extends Controller
{
    use AnakDipantau;

    public function index(Request $request): View
    {
        $anak = $request->user()->ortu->anak;

        return view('ortu.surat-izin', [
            'surat' => SuratIzin::with(['siswa', 'pemroses', 'pengaju'])
                ->whereIn('siswa_id', $anak->pluck('id'))
                ->latest()
                ->paginate(config('lms.per_page')),
            'pilihanAnak' => $anak->mapWithKeys(fn (Siswa $s) => [$s->id => $s->nama.' ('.($s->kelasPada()?->nama_kelas ?? 'tanpa kelas').')'])->all(),
            'anakDipilih' => $this->anakDipantau($request)?->id,
        ]);
    }

    public function store(SuratIzinRequest $request, SuratIzinService $service): RedirectResponse
    {
        $anak = $request->user()->ortu->anak->firstWhere('id', (int) $request->validated('siswa_id'));
        $surat = $service->ajukan($anak, $request->safe()->except(['file', 'siswa_id']), $request->file('file'), $request->user());

        return $surat
            ? back()->with('success', "Surat izin {$anak->nama} terkirim ke wali kelas.")
            : back()->with('error', "{$anak->nama} belum terdaftar di kelas pada tahun ajaran aktif.");
    }

    /** Hanya surat yang diajukan orang tua ini dan belum diproses yang boleh dibatalkan. */
    public function destroy(Request $request, SuratIzin $suratIzin): RedirectResponse
    {
        abort_unless($suratIzin->pengaju_user_id === $request->user()->id, 403);

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
