<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Ortu\Concerns\AnakDipantau;
use App\Models\Pengumuman;
use App\Models\Siswa;
use App\Services\AkademikSiswaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Halaman pantauan (baca-saja) untuk anak yang sedang dipilih orang tua. */
class AnakController extends Controller
{
    use AnakDipantau;

    public function __construct(private AkademikSiswaService $akademik) {}

    public function nilai(Request $request): View|RedirectResponse
    {
        return $this->tampilkan($request, 'ortu.nilai', fn (Siswa $anak) => $this->akademik->nilai($anak));
    }

    public function presensi(Request $request): View|RedirectResponse
    {
        return $this->tampilkan($request, 'ortu.presensi', fn (Siswa $anak) => $this->akademik->presensi($anak));
    }

    public function tugas(Request $request): View|RedirectResponse
    {
        return $this->tampilkan($request, 'ortu.tugas', fn (Siswa $anak) => ['daftar' => $this->akademik->daftarTugas($anak)]);
    }

    public function kuis(Request $request): View|RedirectResponse
    {
        return $this->tampilkan($request, 'ortu.kuis', fn (Siswa $anak) => ['daftar' => $this->akademik->daftarKuis($anak)]);
    }

    public function jadwal(Request $request): View|RedirectResponse
    {
        return $this->tampilkan($request, 'ortu.jadwal', fn (Siswa $anak) => ['jadwal' => $this->akademik->jadwal($anak)]);
    }

    public function pengumuman(Request $request): View
    {
        $kelasIds = $request->user()->ortu->anak->map(fn (Siswa $s) => $s->kelasPada()?->id)->filter()->values()->all();

        $pengumuman = Pengumuman::with(['pembuat.guru', 'pembuat.kepsek', 'pembuat.operator', 'kelas'])
            ->untukOrtu($kelasIds)
            ->latest()
            ->paginate(config('lms.per_page'));

        return view('ortu.pengumuman', compact('pengumuman'));
    }

    /** @param callable(Siswa): array<string, mixed> $data */
    private function tampilkan(Request $request, string $view, callable $data): View|RedirectResponse
    {
        $anak = $this->anakDipantau($request);
        if (! $anak) {
            return redirect()->route('ortu.dashboard');
        }

        return view($view, ['anak' => $anak] + $data($anak));
    }
}
