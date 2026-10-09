<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Siswa;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Memasukkan siswa ke kelas (anggota kelas per tahun ajaran). */
class KelasSiswaController extends Controller
{
    public function show(Request $request, Kelas $kelas): View
    {
        $kelas->load(['jurusan', 'waliKelas', 'tahunAjaran']);
        $cari = $request->string('q')->trim()->value();

        $anggota = $kelas->siswa()->orderBy('nama')->get();

        // Kandidat: siswa aktif yang belum punya kelas di tahun ajaran kelas ini.
        $kandidat = Siswa::where('status', 'aktif')
            ->whereDoesntHave('kelasSiswa', fn ($q) => $q->where('tahun_ajaran_id', $kelas->tahun_ajaran_id))
            ->when($cari, fn ($q) => $q->where(fn ($q) => $q
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('nis', 'like', "%{$cari}%")))
            ->orderBy('nama')
            ->paginate(50, ['*'], 'hal')
            ->withQueryString();

        return view('operator.kelas.siswa', compact('kelas', 'anggota', 'kandidat', 'cari'));
    }

    public function store(Request $request, Kelas $kelas): RedirectResponse
    {
        $data = $request->validate([
            'siswa_id' => ['required', 'array', 'min:1'],
            'siswa_id.*' => [
                'integer',
                Rule::exists('siswa', 'id'),
                Rule::unique('kelas_siswa', 'siswa_id')->where('tahun_ajaran_id', $kelas->tahun_ajaran_id),
            ],
        ], [
            'siswa_id.required' => 'Pilih minimal satu siswa.',
            'siswa_id.*.unique' => 'Sebagian siswa sudah terdaftar di kelas lain pada tahun ajaran ini.',
        ]);

        DB::transaction(function () use ($data, $kelas) {
            foreach ($data['siswa_id'] as $siswaId) {
                KelasSiswa::create([
                    'kelas_id' => $kelas->id,
                    'siswa_id' => $siswaId,
                    'tahun_ajaran_id' => $kelas->tahun_ajaran_id,
                ]);
            }
        });

        $jumlah = count($data['siswa_id']);
        ActivityLogger::log('tambah_anggota_kelas', "Memasukkan {$jumlah} siswa ke kelas {$kelas->nama_kelas}");

        return back()->with('success', "{$jumlah} siswa berhasil dimasukkan ke kelas {$kelas->nama_kelas}.");
    }

    public function destroy(Kelas $kelas, Siswa $siswa): RedirectResponse
    {
        KelasSiswa::where('kelas_id', $kelas->id)->where('siswa_id', $siswa->id)->delete();
        ActivityLogger::log('hapus_anggota_kelas', "Mengeluarkan {$siswa->nama} dari kelas {$kelas->nama_kelas}");

        return back()->with('success', "{$siswa->nama} dikeluarkan dari kelas {$kelas->nama_kelas}.");
    }
}
