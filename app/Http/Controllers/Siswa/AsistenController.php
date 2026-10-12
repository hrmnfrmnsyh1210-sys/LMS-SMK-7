<?php

namespace App\Http\Controllers\Siswa;

use App\Exceptions\AiGagalException;
use App\Http\Controllers\Controller;
use App\Services\AsistenAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Asisten belajar AI. Riwayat obrolan hanya disimpan di browser, tidak di database. */
class AsistenController extends Controller
{
    public function index(Request $request, AsistenAiService $ai): View
    {
        $mapel = $request->user()->siswa->pengampu()->with('mapel')->get()->sortBy('mapel.nama_mapel');

        return view('siswa.asisten', [
            'daftarMapel' => $mapel->mapWithKeys(fn ($p) => [$p->id => $p->mapel->nama_mapel])->all(),
            'mapelTerpilih' => $request->integer('mapel') ?: null,
            'aiAktif' => $ai->aktif(),
        ]);
    }

    public function kirim(Request $request, AsistenAiService $ai): JsonResponse
    {
        $data = $request->validate([
            'pesan' => ['required', 'string', 'max:2000'],
            'pengampu_id' => ['nullable', 'integer'],
            'riwayat' => ['nullable', 'array', 'max:20'],
            'riwayat.*.role' => ['required', 'in:user,assistant'],
            'riwayat.*.content' => ['required', 'string', 'max:8000'],
        ]);

        $siswa = $request->user()->siswa;
        // Hanya mapel di kelas siswa sendiri yang boleh dijadikan konteks.
        $pengampu = isset($data['pengampu_id']) ? $siswa->pengampu()->find($data['pengampu_id']) : null;

        try {
            $balasan = $ai->tanyaAsisten($siswa, $pengampu, $data['riwayat'] ?? [], $data['pesan']);
        } catch (AiGagalException $e) {
            return response()->json(['pesan' => $e->getMessage()], 503);
        }

        return response()->json(['balasan' => $balasan, 'html' => $ai->html($balasan)]);
    }
}
