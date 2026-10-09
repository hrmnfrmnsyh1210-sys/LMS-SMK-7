<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\JawabanKuis;
use App\Models\Kuis;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Services\ActivityLogger;
use App\Services\AkademikSiswaService;
use App\Services\PengerjaanKuisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Kuis, UTS, dan UAS dari sisi siswa: daftar, pengerjaan dengan timer & autosave, dan hasil. */
class KuisController extends Controller
{
    public function __construct(private PengerjaanKuisService $service, private AkademikSiswaService $akademik) {}

    public function index(Request $request): View
    {
        $daftar = $this->akademik->daftarKuis($request->user()->siswa);

        return view('siswa.kuis.index', compact('daftar'));
    }

    public function mapel(Request $request, Pengampu $pengampu): View
    {
        $daftar = $this->akademik->daftarKuis($request->user()->siswa, collect([$pengampu->id]));

        return view('siswa.kuis.mapel', compact('pengampu', 'daftar'));
    }

    public function show(Request $request, Pengampu $pengampu, Kuis $kuis): View
    {
        $jawaban = $this->jawabanSiswa($kuis, $request->user()->siswa);
        if ($jawaban) {
            $jawaban = $this->service->submitBilaWaktuHabis($jawaban);
        }

        $kuis->loadCount('soal');
        // Pembahasan (kunci jawaban) baru dibuka setelah kuis ditutup agar tidak bocor ke siswa lain.
        $bolehPembahasan = $jawaban?->sudahSubmit() && $kuis->tampilkan_hasil && $kuis->status_waktu === 'selesai';
        if ($bolehPembahasan) {
            $jawaban->load('detail');
        }

        return view('siswa.kuis.show', [
            'pengampu' => $pengampu,
            'kuis' => $kuis,
            'jawaban' => $jawaban,
            'status' => $this->service->statusSiswa($kuis, $jawaban),
            'soal' => $bolehPembahasan ? $this->service->soalTerurut($jawaban) : collect(),
            'bolehPembahasan' => $bolehPembahasan,
        ]);
    }

    public function mulai(Request $request, Pengampu $pengampu, Kuis $kuis): RedirectResponse
    {
        $siswa = $request->user()->siswa;
        $jawaban = $this->jawabanSiswa($kuis, $siswa);

        if (! $jawaban) {
            if ($kuis->status_waktu !== 'berlangsung') {
                return back()->with('error', 'Kuis ini sedang tidak dibuka.');
            }
            if (! $kuis->soal()->exists()) {
                return back()->with('error', 'Kuis ini belum memiliki soal.');
            }

            $jawaban = $this->service->mulai($kuis, $siswa);
            ActivityLogger::log('mulai_kuis', "Mulai mengerjakan {$kuis->label_jenis} \"{$kuis->judul}\" ({$pengampu->label})");
        }

        return redirect()->route('siswa.kuis.kerjakan', [$pengampu, $kuis]);
    }

    public function kerjakan(Request $request, Pengampu $pengampu, Kuis $kuis): View|RedirectResponse
    {
        $jawaban = $this->jawabanSiswa($kuis, $request->user()->siswa);
        if (! $jawaban) {
            return redirect()->route('siswa.kuis.show', [$pengampu, $kuis]);
        }

        $jawaban = $this->service->submitBilaWaktuHabis($jawaban);
        if ($jawaban->sudahSubmit()) {
            return redirect()->route('siswa.kuis.show', [$pengampu, $kuis])->with('warning', 'Waktu pengerjaan sudah berakhir.');
        }

        return view('siswa.kuis.kerjakan', [
            'pengampu' => $pengampu,
            'kuis' => $kuis,
            'jawaban' => $jawaban,
            'soal' => $this->service->soalTerurut($jawaban),
            'isian' => $jawaban->detail()->pluck('jawaban', 'soal_id'),
            'sisaDetik' => $this->service->sisaDetik($jawaban),
        ]);
    }

    /** Autosave satu jawaban (dipanggil via fetch). */
    public function jawab(Request $request, Pengampu $pengampu, Kuis $kuis): JsonResponse
    {
        $data = $request->validate([
            'soal_id' => ['required', 'integer', Rule::exists('soal', 'id')->where('kuis_id', $kuis->id)],
            'jawaban' => ['nullable', 'string', 'max:10000'],
        ]);

        $jawaban = $this->jawabanSiswa($kuis, $request->user()->siswa);
        if (! $jawaban || ! $this->service->masihBisaMenjawab($jawaban)) {
            return response()->json(['pesan' => 'Waktu pengerjaan sudah berakhir.'], 422);
        }

        $this->service->simpanJawaban($jawaban, $kuis->soal()->findOrFail($data['soal_id']), $data['jawaban'] ?? null);

        return response()->json(['tersimpan' => true, 'sisa_detik' => $this->service->sisaDetik($jawaban)]);
    }

    public function pindahTab(Request $request, Pengampu $pengampu, Kuis $kuis): JsonResponse
    {
        $jawaban = $this->jawabanSiswa($kuis, $request->user()->siswa);
        abort_unless($jawaban, 404);

        $this->service->catatPindahTab($jawaban);

        return response()->json(['jumlah' => $jawaban->jumlah_pindah_tab]);
    }

    public function submit(Request $request, Pengampu $pengampu, Kuis $kuis): RedirectResponse
    {
        $request->validate(['jawaban' => ['array'], 'jawaban.*' => ['nullable', 'string', 'max:10000']]);

        $jawaban = $this->jawabanSiswa($kuis, $request->user()->siswa);
        abort_unless($jawaban, 404);

        if (! $jawaban->sudahSubmit()) {
            $this->service->submit($jawaban, $request->input('jawaban', []));
            ActivityLogger::log('submit_kuis', "Mengirim {$kuis->label_jenis} \"{$kuis->judul}\" ({$pengampu->label})");
        }

        return redirect()->route('siswa.kuis.show', [$pengampu, $kuis])->with('success', 'Jawaban berhasil dikirim.');
    }

    private function jawabanSiswa(Kuis $kuis, Siswa $siswa): ?JawabanKuis
    {
        return $kuis->jawaban()->where('siswa_id', $siswa->id)->first()?->setRelation('kuis', $kuis);
    }
}
