<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Pengampu;
use App\Services\PresensiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PresensiController extends Controller
{
    public function __construct(private PresensiService $service) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pengampu_id' => ['required', 'integer', 'exists:pengampu,id'],
            'status' => ['required', Rule::in(['hadir', 'izin', 'sakit'])],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $pengampu = Pengampu::with(['kelas', 'mapel'])->findOrFail($data['pengampu_id']);
        $siswa = $request->user()->siswa;

        $this->service->absenMandiri($siswa, $pengampu, $data['status'], $data['catatan'] ?? null);

        return back()->with('success', "Presensi mata pelajaran {$pengampu->mapel->nama_mapel} berhasil dicatat.");
    }
}
