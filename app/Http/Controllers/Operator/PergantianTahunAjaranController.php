<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\TahunAjaran;
use App\Services\ActivityLogger;
use App\Services\TahunAjaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PergantianTahunAjaranController extends Controller
{
    public function index(): View
    {
        return view('operator.pergantian-tahun-ajaran.index', [
            'daftarTahun' => TahunAjaran::withCount('kelas')->orderByDesc('nama')->orderByDesc('semester')->get(),
            'aktif' => TahunAjaran::aktif(),
        ]);
    }

    public function store(Request $request, TahunAjaranService $service): RedirectResponse
    {
        $data = $request->validate([
            'tujuan_id' => ['required', 'exists:tahun_ajaran,id'],
            'salin_data' => ['boolean'],
            'konfirmasi' => ['accepted'],
        ], ['konfirmasi.accepted' => 'Centang konfirmasi sebelum mengganti tahun ajaran.'], ['tujuan_id' => 'tahun ajaran tujuan']);

        $sumber = TahunAjaran::aktif();
        $tujuan = TahunAjaran::findOrFail($data['tujuan_id']);

        if ($sumber?->id === $tujuan->id) {
            return back()->with('error', 'Tahun ajaran tersebut sudah aktif.');
        }

        $hasil = $service->gantiTahunAjaran($tujuan, $sumber, $request->boolean('salin_data'));

        $ringkasan = $request->boolean('salin_data')
            ? " Disalin: {$hasil['kelas']} kelas, {$hasil['siswa']} anggota kelas, {$hasil['pengampu']} pengampu, {$hasil['jadwal']} jadwal."
            : '';

        ActivityLogger::log('ganti_tahun_ajaran', 'Ganti tahun ajaran '.($sumber?->label ?? '-')." → {$tujuan->label}.{$ringkasan}");

        return redirect()->route('operator.pergantian-tahun-ajaran.index')
            ->with('success', "Tahun ajaran aktif sekarang {$tujuan->label}.{$ringkasan}");
    }
}
