<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\KelasSiswa;
use App\Models\Pengampu;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelasAjarController extends Controller
{
    public function index(Request $request): View
    {
        $pengampu = Pengampu::with(['mapel', 'kelas.jurusan', 'jadwal'])
            ->withCount(['materi', 'tugas', 'kuis'])
            ->tahunAktif()
            ->where('guru_id', $request->user()->guru->id)
            ->get()
            ->sortBy(fn ($p) => $p->kelas->nama_kelas.$p->mapel->nama_mapel)
            ->values();

        $jumlahSiswa = KelasSiswa::where('tahun_ajaran_id', TahunAjaran::aktifId())
            ->whereIn('kelas_id', $pengampu->pluck('kelas_id'))
            ->whereHas('siswa', fn ($q) => $q->where('status', 'aktif'))
            ->selectRaw('kelas_id, COUNT(*) as jumlah')
            ->groupBy('kelas_id')
            ->pluck('jumlah', 'kelas_id');

        return view('guru.kelas.index', compact('pengampu', 'jumlahSiswa'));
    }

    /** Halaman utama kelas ajar: daftar siswa. */
    public function show(Pengampu $pengampu): View
    {
        $pengampu->load(['mapel', 'kelas.jurusan', 'kelas.waliKelas', 'jadwal']);

        return view('guru.kelas.show', [
            'pengampu' => $pengampu,
            'siswa' => $pengampu->daftarSiswa(),
        ]);
    }
}
