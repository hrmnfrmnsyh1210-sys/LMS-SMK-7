<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Concerns\MenyajikanLaporan;
use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Pengampu;
use App\Models\TahunAjaran;
use App\Services\AsistenAiService;
use App\Services\LaporanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Laporan nilai & kehadiran mapel yang diampu, serta kelas yang diwalikan (termasuk tahun ajaran lalu). */
class LaporanController extends Controller
{
    use MenyajikanLaporan;

    public function __construct(private LaporanService $service) {}

    public function index(Request $request, AsistenAiService $ai): View
    {
        $guru = $request->user()->guru;
        $filter = $this->filter($request, $guru);

        return view('guru.laporan', $filter + [
            'laporan' => $this->buat($filter),
            'daftarPengampu' => $this->daftarPengampu($guru),
            'daftarKelas' => $this->daftarKelas($guru),
            'aiAktif' => $ai->aktif(),
        ]);
    }

    public function ekspor(Request $request): Response
    {
        $request->validate(['format' => ['required', 'in:pdf,excel']]);
        $laporan = $this->buat($this->filter($request, $request->user()->guru));
        abort_unless($laporan, 404, 'Pilih laporan yang ingin diekspor.');

        return $this->unduhLaporan($laporan, $request->input('format'));
    }

    public function analisis(Request $request, AsistenAiService $ai): JsonResponse
    {
        $laporan = $this->buat($this->filter($request, $request->user()->guru));
        abort_unless($laporan, 404);

        return $this->analisisLaporan($laporan, $ai);
    }

    /**
     * Pengampu & kelas hanya dicari di antara milik guru ini, sehingga ID milik guru lain diabaikan.
     *
     * @return array{jenis: string, pengampu: ?Pengampu, kelas: ?Kelas}
     */
    private function filter(Request $request, Guru $guru): array
    {
        $jenis = array_key_exists($request->input('jenis'), LaporanService::JENIS_GURU) ? $request->input('jenis') : 'nilai_mapel';

        $pengampu = $request->filled('pengampu_id')
            ? $guru->pengampu()->find($request->integer('pengampu_id'))
            : $guru->pengampu()->tahunAktif()->first();

        $kelas = $request->filled('kelas_id')
            ? $guru->kelasWali()->find($request->integer('kelas_id'))
            : $guru->kelasWaliAktif();

        return compact('jenis', 'pengampu', 'kelas');
    }

    /**
     * @param  array{jenis: string, pengampu: ?Pengampu, kelas: ?Kelas}  $filter
     * @return array<string, mixed>|null
     */
    private function buat(array $filter): ?array
    {
        return match ($filter['jenis']) {
            'nilai_mapel' => $filter['pengampu'] ? $this->service->nilaiMapel($filter['pengampu']) : null,
            'kehadiran_mapel' => $filter['pengampu'] ? $this->service->kehadiranMapel($filter['pengampu']) : null,
            'nilai_kelas' => $filter['kelas'] ? $this->service->nilaiKelas($filter['kelas']) : null,
            'kehadiran_kelas' => $filter['kelas'] ? $this->service->kehadiranKelas($filter['kelas']) : null,
        };
    }

    /** @return Collection<int, string> pengampu_id => label, tahun ajaran terbaru dahulu */
    private function daftarPengampu(Guru $guru): Collection
    {
        return $guru->pengampu()->with(['mapel', 'kelas', 'tahunAjaran'])->get()
            ->sortBy([fn ($a, $b) => $this->urutTahun($b->tahunAjaran) <=> $this->urutTahun($a->tahunAjaran), fn ($a, $b) => $a->label <=> $b->label])
            ->mapWithKeys(fn (Pengampu $p) => [$p->id => $p->label.' ('.$p->tahunAjaran->label.')']);
    }

    /** @return Collection<int, string> kelas_id => label */
    private function daftarKelas(Guru $guru): Collection
    {
        return $guru->kelasWali()->with('tahunAjaran')->get()
            ->sortByDesc(fn (Kelas $k) => $this->urutTahun($k->tahunAjaran))
            ->mapWithKeys(fn (Kelas $k) => [$k->id => $k->nama_kelas.' ('.$k->tahunAjaran->label.')']);
    }

    private function urutTahun(TahunAjaran $tahun): string
    {
        return $tahun->nama.($tahun->semester === 'genap' ? '2' : '1');
    }
}
