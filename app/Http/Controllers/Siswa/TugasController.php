<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Siswa\KumpulTugasRequest;
use App\Models\Pengampu;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use App\Services\ActivityLogger;
use App\Services\AkademikSiswaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TugasController extends Controller
{
    public function __construct(private AkademikSiswaService $akademik) {}

    /** Semua tugas dari seluruh mapel siswa pada tahun ajaran aktif. */
    public function index(Request $request): View
    {
        $daftar = $this->akademik->daftarTugas($request->user()->siswa);

        $ringkasan = $daftar->countBy('status');
        $status = $request->input('status');
        if (array_key_exists((string) $status, Tugas::STATUS_SISWA)) {
            $daftar = $daftar->where('status', $status);
        }

        return view('siswa.tugas.index', compact('daftar', 'ringkasan', 'status'));
    }

    /** Tugas pada satu mapel. */
    public function mapel(Request $request, Pengampu $pengampu): View
    {
        $daftar = $this->akademik->daftarTugas($request->user()->siswa, collect([$pengampu->id]));

        return view('siswa.tugas.mapel', compact('pengampu', 'daftar'));
    }

    public function show(Request $request, Pengampu $pengampu, Tugas $tugas): View
    {
        $pengumpulan = $tugas->pengumpulan()->where('siswa_id', $request->user()->siswa->id)->first();

        return view('siswa.tugas.show', [
            'pengampu' => $pengampu,
            'tugas' => $tugas,
            'pengumpulan' => $pengumpulan,
            'status' => $tugas->statusSiswa($pengumpulan),
        ]);
    }

    /** Kumpulkan atau perbarui jawaban; terkunci setelah dinilai guru. Pengumpulan lewat deadline ditandai terlambat. */
    public function kumpul(KumpulTugasRequest $request, Pengampu $pengampu, Tugas $tugas): RedirectResponse
    {
        $siswa = $request->user()->siswa;
        $pengumpulan = $tugas->pengumpulan()->where('siswa_id', $siswa->id)->first();

        if ($pengumpulan?->nilai !== null) {
            return back()->with('error', 'Tugas sudah dinilai sehingga jawaban tidak dapat diubah.');
        }

        $filePath = $pengumpulan?->file_path;
        if ($request->hasFile('file')) {
            if ($filePath) {
                Storage::disk('public')->delete($filePath);
            }
            $filePath = $request->file('file')->store("pengumpulan/{$tugas->id}", 'public');
        }

        PengumpulanTugas::updateOrCreate(
            ['tugas_id' => $tugas->id, 'siswa_id' => $siswa->id],
            ['file_path' => $filePath, 'jawaban_teks' => $request->validated('jawaban_teks'), 'waktu_kumpul' => now()],
        );

        $terlambat = $tugas->sudahLewatDeadline();
        ActivityLogger::log('kumpul_tugas', "Mengumpulkan tugas \"{$tugas->judul}\" ({$pengampu->label})".($terlambat ? ' — terlambat' : ''));

        return redirect()->route('siswa.tugas.show', [$pengampu, $tugas])
            ->with($terlambat ? 'warning' : 'success', $terlambat ? 'Tugas terkirim, tetapi melewati deadline.' : 'Tugas berhasil dikumpulkan.');
    }
}
