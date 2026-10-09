<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\JawabanKuis;
use App\Models\Kuis;
use App\Models\Pengampu;
use App\Notifications\Pemberitahuan;
use App\Services\ActivityLogger;
use App\Services\KuisService;
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

    public function koreksi(Pengampu $pengampu, Kuis $kuis, JawabanKuis $jawaban): View
    {
        $jawaban->load(['siswa', 'detail']);
        $kuis->load('soal');
        $detail = $jawaban->detail->keyBy('soal_id');

        return view('guru.kuis.koreksi', compact('pengampu', 'kuis', 'jawaban', 'detail'));
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
