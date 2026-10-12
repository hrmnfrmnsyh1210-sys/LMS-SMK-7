<?php

namespace App\Http\Controllers\Guru;

use App\Exceptions\AiGagalException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\SimpanSoalAiRequest;
use App\Models\Kuis;
use App\Models\Pengampu;
use App\Services\ActivityLogger;
use App\Services\AsistenAiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Draf soal dari AI: dibuat → ditinjau/diubah guru → hanya yang dipilih disimpan ke bank soal. */
class SoalAiController extends Controller
{
    public function buat(Request $request, Pengampu $pengampu, Kuis $kuis, AsistenAiService $ai): RedirectResponse
    {
        if ($kuis->terkunci()) {
            return $this->tolakTerkunci($pengampu, $kuis);
        }

        $data = $request->validate([
            'topik' => ['required', 'string', 'max:500'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:20'],
            'tipe' => ['required', Rule::in(array_keys(AsistenAiService::TIPE_SOAL))],
            'kesulitan' => ['required', Rule::in(array_keys(AsistenAiService::KESULITAN))],
            'materi_id' => ['nullable', Rule::exists('materi', 'id')->where('pengampu_id', $pengampu->id)],
        ], [], ['jumlah' => 'jumlah soal', 'materi_id' => 'materi']);

        try {
            $soal = $ai->buatSoal(
                $pengampu,
                $data['topik'],
                (int) $data['jumlah'],
                $data['tipe'],
                AsistenAiService::KESULITAN[$data['kesulitan']],
                isset($data['materi_id']) ? $pengampu->materi()->find($data['materi_id']) : null,
            );
        } catch (AiGagalException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        ActivityLogger::log('ai_buat_soal', 'AI membuat '.count($soal)." draf soal untuk \"{$kuis->judul}\"");

        // Disimpan di session (bukan flash) agar draf bertahan saat dimuat ulang atau validasi gagal.
        $request->session()->put($this->kunciSesi($kuis), $soal);

        return redirect()->route('guru.soal.ai.pratinjau', [$pengampu, $kuis]);
    }

    public function pratinjau(Request $request, Pengampu $pengampu, Kuis $kuis): View|RedirectResponse
    {
        $soal = $request->session()->get($this->kunciSesi($kuis));
        if (! $soal) {
            return redirect()->route('guru.kuis.show', [$pengampu, $kuis]);
        }

        return view('guru.soal.ai', compact('pengampu', 'kuis', 'soal'));
    }

    public function simpan(SimpanSoalAiRequest $request, Pengampu $pengampu, Kuis $kuis): RedirectResponse
    {
        if ($kuis->terkunci()) {
            return $this->tolakTerkunci($pengampu, $kuis);
        }

        $soal = $request->dataSoal();
        $kuis->soal()->createMany($soal);
        $request->session()->forget($this->kunciSesi($kuis));

        ActivityLogger::log('ai_simpan_soal', count($soal)." soal AI disimpan ke \"{$kuis->judul}\"");

        return redirect()->route('guru.kuis.show', [$pengampu, $kuis])->with('success', count($soal).' soal dari AI berhasil ditambahkan.');
    }

    private function kunciSesi(Kuis $kuis): string
    {
        return "soal_ai.{$kuis->id}";
    }

    private function tolakTerkunci(Pengampu $pengampu, Kuis $kuis): RedirectResponse
    {
        return redirect()->route('guru.kuis.show', [$pengampu, $kuis])
            ->with('error', 'Soal tidak dapat diubah karena sudah ada siswa yang mengerjakan kuis ini.');
    }
}
