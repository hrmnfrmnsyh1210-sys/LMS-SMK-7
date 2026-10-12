<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MenyajikanLaporan;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Services\AsistenAiService;
use App\Services\LaporanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Laporan tingkat sekolah untuk operator dan kepala sekolah (route berprefiks role). */
class LaporanController extends Controller
{
    use MenyajikanLaporan;

    public function __construct(private LaporanService $service) {}

    public function index(Request $request, AsistenAiService $ai): View
    {
        $filter = $this->filter($request);

        return view('laporan.index', $filter + [
            'laporan' => $this->buat($filter),
            'daftarTahun' => TahunAjaran::orderByDesc('nama')->orderByDesc('semester')->get(),
            'daftarKelas' => $filter['tahun'] ? Kelas::where('tahun_ajaran_id', $filter['tahun']->id)->orderBy('nama_kelas')->get() : collect(),
            'prefix' => $request->user()->role,
            'aiAktif' => $ai->aktif(),
        ]);
    }

    public function ekspor(Request $request): Response
    {
        $request->validate(['format' => ['required', 'in:pdf,excel']]);
        $laporan = $this->buat($this->filter($request));
        abort_unless($laporan, 404, 'Pilih laporan yang ingin diekspor.');

        return $this->unduhLaporan($laporan, $request->input('format'));
    }

    public function analisis(Request $request, AsistenAiService $ai): JsonResponse
    {
        $laporan = $this->buat($this->filter($request));
        abort_unless($laporan, 404);

        return $this->analisisLaporan($laporan, $ai);
    }

    /**
     * @return array{jenis: string, tahun: ?TahunAjaran, kelas: ?Kelas}
     */
    private function filter(Request $request): array
    {
        $jenis = array_key_exists($request->input('jenis'), LaporanService::JENIS_SEKOLAH) ? $request->input('jenis') : 'akademik';
        $tahun = $request->filled('tahun_ajaran_id') ? TahunAjaran::find($request->integer('tahun_ajaran_id')) : TahunAjaran::aktif();
        $kelas = $tahun && $request->filled('kelas_id')
            ? Kelas::where('tahun_ajaran_id', $tahun->id)->find($request->integer('kelas_id'))
            : null;

        return compact('jenis', 'tahun', 'kelas');
    }

    /**
     * @param  array{jenis: string, tahun: ?TahunAjaran, kelas: ?Kelas}  $filter
     * @return array<string, mixed>|null
     */
    private function buat(array $filter): ?array
    {
        if (! $filter['tahun']) {
            return null;
        }

        return match ($filter['jenis']) {
            'akademik' => $this->service->akademik($filter['tahun']),
            'kesiswaan' => $this->service->kesiswaan($filter['tahun']),
            'kehadiran' => $filter['kelas'] ? $this->service->kehadiranKelas($filter['kelas']) : null,
            'nilai' => $filter['kelas'] ? $this->service->nilaiKelas($filter['kelas']) : null,
        };
    }
}
