<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\ProsesSuratIzinRequest;
use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Presensi;
use App\Models\SuratIzin;
use App\Services\ActivityLogger;
use App\Services\RankingService;
use App\Services\SuratIzinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Fitur khusus guru yang menjadi wali kelas pada tahun ajaran aktif. */
class WaliKelasController extends Controller
{
    /** Rekap nilai semua mapel, kehadiran, dan ranking kelas wali. */
    public function rekap(Request $request, RankingService $ranking): View
    {
        $kelas = $this->kelasWali($request);
        $kelas->load(['pengampu.mapel', 'pengampu.guru', 'jurusan']);

        $pengampu = $kelas->pengampu->sortBy('mapel.nama_mapel')->values();
        $nilai = Nilai::whereIn('pengampu_id', $pengampu->pluck('id'))->get()->groupBy('siswa_id');

        $kehadiran = Presensi::whereIn('pengampu_id', $pengampu->pluck('id'))
            ->selectRaw('siswa_id, status, COUNT(*) as jumlah')
            ->groupBy('siswa_id', 'status')
            ->get()
            ->groupBy('siswa_id')
            ->map(fn ($baris) => $baris->pluck('jumlah', 'status'));

        return view('guru.wali.rekap', [
            'kelas' => $kelas,
            'pengampu' => $pengampu,
            'ranking' => $ranking->kelas($kelas),
            'nilai' => $nilai,
            'kehadiran' => $kehadiran,
            'kkm' => (float) pengaturan('kkm', 75),
        ]);
    }

    public function suratIzin(Request $request): View
    {
        $kelas = $this->kelasWali($request);
        $status = $request->input('status', 'menunggu');

        $surat = SuratIzin::with(['siswa', 'pemroses'])
            ->where('kelas_id', $kelas->id)
            ->when(in_array($status, ['menunggu', 'disetujui', 'ditolak'], true), fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(config('lms.per_page'))
            ->withQueryString();

        $jumlahMenunggu = SuratIzin::where('kelas_id', $kelas->id)->where('status', 'menunggu')->count();

        return view('guru.wali.surat-izin', compact('kelas', 'surat', 'status', 'jumlahMenunggu'));
    }

    public function proses(ProsesSuratIzinRequest $request, SuratIzin $suratIzin, SuratIzinService $service): RedirectResponse
    {
        $data = $request->validated();
        $diperbarui = $service->proses($suratIzin, $data['status'], $data['catatan'] ?? null, $request->user()->guru);

        ActivityLogger::log('proses_surat_izin', "Surat {$suratIzin->jenis} {$suratIzin->siswa->nama} {$data['status']}");

        $pesan = "Surat izin {$suratIzin->siswa->nama} {$data['status']}.";
        if ($diperbarui) {
            $pesan .= " {$diperbarui} data presensi diperbarui menjadi {$suratIzin->jenis}.";
        }

        return back()->with('success', $pesan);
    }

    private function kelasWali(Request $request): Kelas
    {
        $kelas = $request->user()->guru->kelasWaliAktif();
        abort_unless($kelas, 403, 'Anda bukan wali kelas pada tahun ajaran aktif.');

        return $kelas;
    }
}
