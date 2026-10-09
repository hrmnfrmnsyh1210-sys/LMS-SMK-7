<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Ortu\Concerns\AnakDipantau;
use App\Http\Controllers\Siswa\DashboardController as DashboardSiswa;
use App\Models\Jadwal;
use App\Models\JawabanKuis;
use App\Models\PengumpulanTugas;
use App\Models\Pengumuman;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Services\AkademikSiswaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use AnakDipantau;

    public function __construct(private AkademikSiswaService $akademik) {}

    public function __invoke(Request $request): View
    {
        $ortu = $request->user()->ortu;
        $anak = $this->anakDipantau($request);

        $semuaAnak = $ortu->anak->map(fn (Siswa $s) => [
            'siswa' => $s,
            'kelas' => $s->kelasPada(),
            'ringkasan' => $this->akademik->ringkasan($s),
        ]);

        if (! $anak) {
            return view('ortu.dashboard', ['ortu' => $ortu, 'anak' => null, 'semuaAnak' => $semuaAnak]);
        }

        $kelas = $anak->kelasPada();
        $kelas?->load('waliKelas');
        $pengampuIds = $anak->pengampu()->pluck('id');

        return view('ortu.dashboard', [
            'ortu' => $ortu,
            'anak' => $anak,
            'kelas' => $kelas,
            'semuaAnak' => $semuaAnak,
            'ringkasan' => $semuaAnak->firstWhere('siswa.id', $anak->id)['ringkasan'],
            'jadwalHariIni' => Jadwal::with(['pengampu.mapel', 'pengampu.guru'])
                ->whereIn('pengampu_id', $pengampuIds)
                ->where('hari', DashboardSiswa::HARI[now()->dayOfWeekIso])
                ->orderBy('jam_mulai')
                ->get(),
            'tugasBelum' => $this->akademik->tugasBelumQuery($anak, $pengampuIds)->limit(5)->get(),
            'perhatian' => Presensi::with('pengampu.mapel')
                ->where('siswa_id', $anak->id)
                ->whereIn('pengampu_id', $pengampuIds)
                ->where('status', 'alpa')
                ->latest('tanggal')
                ->limit(5)
                ->get(),
            'nilaiTerbaru' => $this->nilaiTerbaru($anak, $pengampuIds),
            'pengumuman' => Pengumuman::untukOrtu($kelas ? [$kelas->id] : [])->latest()->limit(3)->get(),
        ]);
    }

    /** Ganti anak yang dipantau. */
    public function pilihAnak(Request $request, Siswa $siswa): RedirectResponse
    {
        abort_unless($request->user()->ortu->memantau($siswa), 403);

        $request->session()->put(self::SESSION_ANAK, $siswa->id);

        return back()->with('success', "Sekarang memantau {$siswa->nama}.");
    }

    /**
     * Gabungan nilai tugas & kuis terbaru (kuis hanya bila hasilnya ditampilkan guru).
     *
     * @param  Collection<int, int>  $pengampuIds
     * @return Collection<int, array{jenis: string, judul: string, mapel: string, nilai: float, maks: int, waktu: Carbon}>
     */
    private function nilaiTerbaru(Siswa $anak, Collection $pengampuIds): Collection
    {
        $tugas = PengumpulanTugas::with('tugas.pengampu.mapel')
            ->where('siswa_id', $anak->id)
            ->whereNotNull('nilai')
            ->whereHas('tugas', fn ($q) => $q->whereIn('pengampu_id', $pengampuIds))
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'jenis' => 'Tugas', 'judul' => $p->tugas->judul, 'mapel' => $p->tugas->pengampu->mapel->nama_mapel,
                'nilai' => (float) $p->nilai, 'maks' => $p->tugas->nilai_maks, 'waktu' => $p->updated_at,
            ]);

        $kuis = JawabanKuis::with('kuis.pengampu.mapel')
            ->where('siswa_id', $anak->id)
            ->whereNotNull('total_skor')
            ->whereHas('kuis', fn ($q) => $q->whereIn('pengampu_id', $pengampuIds)->where('tampilkan_hasil', true))
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn ($j) => [
                'jenis' => $j->kuis->label_jenis, 'judul' => $j->kuis->judul, 'mapel' => $j->kuis->pengampu->mapel->nama_mapel,
                'nilai' => (float) $j->total_skor, 'maks' => 100, 'waktu' => $j->updated_at,
            ]);

        return $tugas->concat($kuis)->sortByDesc('waktu')->take(5)->values();
    }
}
