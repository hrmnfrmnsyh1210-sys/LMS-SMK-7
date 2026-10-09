<?php

namespace App\Http\Controllers\Guru;

use App\Exports\NilaiExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\BobotNilaiRequest;
use App\Http\Requests\Guru\NilaiRequest;
use App\Models\Pengampu;
use App\Services\ActivityLogger;
use App\Services\NilaiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NilaiController extends Controller
{
    public function __construct(private NilaiService $service) {}

    /** Rekap selalu dihitung ulang agar nilai tugas & kuis terbaru. */
    public function index(Pengampu $pengampu): View
    {
        return view('guru.nilai.index', [
            'pengampu' => $pengampu,
            'bobot' => $pengampu->bobot(),
            'nilai' => $this->service->hitungRekap($pengampu),
            'adaUjian' => $pengampu->kuis()->whereIn('jenis', ['uts', 'uas'])->exists(),
        ]);
    }

    public function bobot(BobotNilaiRequest $request, Pengampu $pengampu): RedirectResponse
    {
        $pengampu->bobot()->update($request->validated());
        $this->service->hitungRekap($pengampu);

        ActivityLogger::log('atur_bobot', "Mengatur bobot nilai {$pengampu->label}");

        return back()->with('success', 'Bobot nilai disimpan dan nilai akhir dihitung ulang.');
    }

    public function simpan(NilaiRequest $request, Pengampu $pengampu): RedirectResponse
    {
        $this->service->hitungRekap($pengampu, $request->validated('nilai', []));

        ActivityLogger::log('input_nilai', "Menyimpan nilai UTS/UAS {$pengampu->label}");

        return back()->with('success', 'Nilai tersimpan dan nilai akhir dihitung ulang.');
    }

    public function ambilUjian(Pengampu $pengampu): RedirectResponse
    {
        $hasil = $this->service->ambilUtsUasDariKuis($pengampu);
        $diambil = collect($hasil)->filter()->keys()->map(fn ($j) => strtoupper($j))->implode(' & ');

        return back()->with(
            $diambil ? 'success' : 'warning',
            $diambil ? "Nilai {$diambil} diambil dari hasil kuis." : 'Belum ada kuis berjenis UTS/UAS.'
        );
    }

    public function ekspor(Pengampu $pengampu): BinaryFileResponse
    {
        $pengampu->load(['mapel', 'kelas', 'guru', 'tahunAjaran']);
        $nilai = $this->service->hitungRekap($pengampu);

        $nama = Str::slug("nilai {$pengampu->mapel->nama_mapel} {$pengampu->kelas->nama_kelas}").'.xlsx';

        return Excel::download(new NilaiExport($pengampu, $nilai), $nama);
    }
}
