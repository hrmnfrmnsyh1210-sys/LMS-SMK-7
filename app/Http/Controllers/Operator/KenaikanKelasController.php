<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Services\ActivityLogger;
use App\Services\KenaikanKelasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Wizard kenaikan kelas:
 * 1. pilih tahun ajaran sumber & tujuan,
 * 2. petakan kelas lama → kelas baru (XII → lulus),
 * 3. proses.
 */
class KenaikanKelasController extends Controller
{
    public function __construct(private KenaikanKelasService $service) {}

    public function index(Request $request): View
    {
        $daftarTahun = TahunAjaran::orderByDesc('nama')->orderByDesc('semester')->get();
        $sumber = TahunAjaran::find($request->integer('sumber_id'));
        $tujuan = TahunAjaran::find($request->integer('tujuan_id'));

        $kelasSumber = collect();
        $kelasTujuan = collect();

        if ($sumber && $tujuan && $sumber->id !== $tujuan->id) {
            $kelasSumber = Kelas::with('jurusan')
                ->withCount(['siswa' => fn ($q) => $q->where('siswa.status', 'aktif')])
                ->where('tahun_ajaran_id', $sumber->id)
                ->orderByRaw("FIELD(tingkat, 'X', 'XI', 'XII')")->orderBy('nama_kelas')
                ->get();
            $kelasTujuan = Kelas::where('tahun_ajaran_id', $tujuan->id)->orderBy('nama_kelas')->get();
        }

        return view('operator.kenaikan-kelas.index', [
            'daftarTahun' => $daftarTahun,
            'sumber' => $sumber,
            'tujuan' => $tujuan,
            'kelasSumber' => $kelasSumber,
            'kelasTujuan' => $kelasTujuan,
            'service' => $this->service,
        ]);
    }

    public function buatKelas(Request $request): RedirectResponse
    {
        [$sumber, $tujuan] = $this->validasiTahun($request);

        $jumlah = $this->service->buatKelasTujuan($sumber, $tujuan);
        ActivityLogger::log('kenaikan_kelas', "Membuat {$jumlah} kelas tujuan di {$tujuan->label}");

        return redirect()->route('operator.kenaikan-kelas.index', ['sumber_id' => $sumber->id, 'tujuan_id' => $tujuan->id])
            ->with('success', "{$jumlah} kelas baru dibuat di tahun ajaran {$tujuan->label}.");
    }

    public function proses(Request $request): RedirectResponse
    {
        [$sumber, $tujuan] = $this->validasiTahun($request);

        $request->validate([
            'pemetaan' => ['required', 'array'],
            'konfirmasi' => ['accepted'],
        ], ['konfirmasi.accepted' => 'Centang konfirmasi sebelum memproses kenaikan kelas.']);

        $hasil = $this->service->proses($sumber, $tujuan, $request->input('pemetaan'));

        ActivityLogger::log('kenaikan_kelas', "Kenaikan kelas {$sumber->label} → {$tujuan->label}: {$hasil['naik']} naik, {$hasil['lulus']} lulus, {$hasil['dilewati']} dilewati");

        return redirect()->route('operator.kenaikan-kelas.index')
            ->with('success', "Kenaikan kelas selesai: {$hasil['naik']} siswa naik kelas, {$hasil['lulus']} siswa lulus, {$hasil['dilewati']} siswa dilewati.");
    }

    /** @return array{0: TahunAjaran, 1: TahunAjaran} */
    private function validasiTahun(Request $request): array
    {
        $request->validate([
            'sumber_id' => ['required', 'exists:tahun_ajaran,id'],
            'tujuan_id' => ['required', 'exists:tahun_ajaran,id', Rule::notIn([$request->input('sumber_id')])],
        ], ['tujuan_id.not_in' => 'Tahun ajaran tujuan harus berbeda dengan sumber.']);

        return [TahunAjaran::findOrFail($request->sumber_id), TahunAjaran::findOrFail($request->tujuan_id)];
    }
}
