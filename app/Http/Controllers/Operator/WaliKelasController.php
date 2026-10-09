<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Kelas;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Penentuan wali kelas untuk semua kelas tahun ajaran aktif sekaligus. */
class WaliKelasController extends Controller
{
    public function index(): View
    {
        return view('operator.wali-kelas.index', [
            'kelas' => Kelas::with(['jurusan', 'waliKelas'])->withCount('siswa')->tahunAktif()->orderBy('nama_kelas')->get(),
            'daftarGuru' => Guru::orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'wali' => ['array'],
            'wali.*' => ['nullable', 'exists:guru,id'],
        ]);

        $wali = array_filter($data['wali'] ?? []);
        if (count($wali) !== count(array_unique($wali))) {
            return back()->withInput()->with('error', 'Satu guru hanya boleh menjadi wali untuk satu kelas.');
        }

        DB::transaction(function () use ($data) {
            $kelas = Kelas::tahunAktif()->whereIn('id', array_keys($data['wali'] ?? []))->get();
            foreach ($kelas as $k) {
                $k->update(['wali_kelas_id' => $data['wali'][$k->id] ?: null]);
            }
        });

        ActivityLogger::log('atur_wali_kelas', 'Memperbarui penetapan wali kelas');

        return back()->with('success', 'Wali kelas berhasil disimpan.');
    }
}
