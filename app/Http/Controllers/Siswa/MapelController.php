<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Pengampu;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Daftar mata pelajaran siswa dan materi per mapel. */
class MapelController extends Controller
{
    public function index(Request $request): View
    {
        $siswa = $request->user()->siswa;

        $pengampu = $siswa->pengampu()
            ->with(['mapel', 'guru', 'jadwal'])
            ->withCount(['materi', 'tugas', 'kuis'])
            ->get()
            ->sortBy('mapel.nama_mapel')
            ->values();

        return view('siswa.mapel.index', ['pengampu' => $pengampu, 'kelas' => $siswa->kelasPada()]);
    }

    public function show(Pengampu $pengampu): View
    {
        $materi = $pengampu->materi()->orderBy('pertemuan_ke')->latest('id')->get()->groupBy('pertemuan_ke');

        return view('siswa.mapel.show', compact('pengampu', 'materi'));
    }
}
