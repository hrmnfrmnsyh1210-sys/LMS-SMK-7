<?php

namespace App\Http\Controllers\Guru;

use App\Exceptions\AiGagalException;
use App\Http\Controllers\Controller;
use App\Models\JawabanKuis;
use App\Models\Kuis;
use App\Models\Pengampu;
use App\Notifications\Pemberitahuan;
use App\Services\ActivityLogger;
use App\Services\AsistenAiService;
use App\Services\KuisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HasilKuisController extends Controller
{
    public function __construct(private KuisService $service) {}

    public function index(Pengampu $pengampu, Kuis $kuis): View
    {
        $jawaban = $kuis->jawaban()->get()->keyBy('siswa_id');

        $daftar = $pengampu->daftarSiswa()->map(fn ($siswa) => [
            'siswa' => $siswa,
            'jawaban' => $jawaban->get($siswa->id),
        ]);

        $skorFinal = $jawaban->whereNotNull('total_skor')->pluck('total_skor')->map(fn ($s) => (float) $s);
        $ringkasan = [
            'mengerjakan' => $jawaban->filter->sudahSubmit()->count(),
            'menunggu' => $jawaban->filter->menungguKoreksi()->count(),
            'rata' => $skorFinal->isEmpty() ? null : round($skorFinal->avg(), 2),
            'tertinggi' => $skorFinal->max(),
            'terendah' => $skorFinal->min(),
        ];

        return view('guru.kuis.hasil', compact('pengampu', 'kuis', 'daftar', 'ringkasan'));
    }

    public function koreksi(Pengampu $pengampu, Kuis $kuis, JawabanKuis $jawaban, AsistenAiService $ai): View
    {
        $jawaban->load(['siswa', 'detail']);
        $kuis->load('soal');
        $detail = $jawaban->detail->keyBy('soal_id');
        $aiAktif = $ai->aktif();

        return view('guru.kuis.koreksi', compact('pengampu', 'kuis', 'jawaban', 'detail', 'aiAktif'));
    }

    /** Saran skor esai dari AI; guru tetap yang menentukan dan menyimpan skor. */
    public function saranAi(Pengampu $pengampu, Kuis $kuis, JawabanKuis $jawaban, AsistenAiService $ai): JsonResponse
    {
        $esai = $jawaban->detail()->with('soal')->get()
            ->filter(fn ($d) => ! $d->soal->isPilihanGanda() && filled($d->jawaban));

        if ($esai->isEmpty()) {
            return response()->json(['pesan' => 'Tidak ada jawaban esai yang perlu dikoreksi.'], 422);
        }

        try {
            $saran = $ai->saranKoreksiEsai($kuis, $esai);
        } catch (AiGagalException $e) {
            return response()->json(['pesan' => $e->getMessage()], 503);
        }

        ActivityLogger::log('ai_saran_koreksi', "Saran koreksi AI \"{$kuis->judul}\" milik {$jawaban->siswa->nama}");

        return response()->json(['saran' => $saran]);
    }

    public function simpanKoreksi(Request $request, Pengampu $pengampu, Kuis $kuis, JawabanKuis $jawaban): RedirectResponse
    {
        $request->validate(['skor' => ['array'], 'skor.*' => ['nullable', 'numeric', 'min:0']], [], ['skor.*' => 'skor']);

        $jawaban = $this->service->koreksiEsai($jawaban, $request->input('skor', []));

        ActivityLogger::log('koreksi_kuis', "Koreksi \"{$kuis->judul}\" milik {$jawaban->siswa->nama}: ".($jawaban->total_skor ?? 'belum final'));
        if ($jawaban->total_skor !== null && $kuis->tampilkan_hasil) {
            $jawaban->siswa->user?->notify(new Pemberitahuan("Hasil {$kuis->label_jenis} \"{$kuis->judul}\" sudah keluar: {$jawaban->total_skor}"));
        }

        return redirect()->route('guru.kuis.hasil', [$pengampu, $kuis])->with('success', "Koreksi {$jawaban->siswa->nama} tersimpan.");
    }

    /** Hitung ulang skor PG semua jawaban (mis. setelah perbaikan kunci). */
    public function hitungUlang(Pengampu $pengampu, Kuis $kuis): RedirectResponse
    {
        $kuis->jawaban()->whereNotNull('waktu_submit')->get()->each(fn ($j) => $this->service->koreksiOtomatis($j));

        return back()->with('success', 'Skor seluruh siswa dihitung ulang.');
    }
}
