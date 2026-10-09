<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\JadwalRequest;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Pengampu;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalController extends Controller
{
    /** Tampilan jadwal per kelas atau per guru, dikelompokkan per hari. */
    public function index(Request $request): View
    {
        $mode = $request->input('mode') === 'guru' ? 'guru' : 'kelas';
        $daftarKelas = Kelas::tahunAktif()->orderBy('nama_kelas')->get();
        $daftarGuru = Guru::whereHas('pengampu', fn ($q) => $q->tahunAktif())->orderBy('nama')->get();

        $pilihanId = $request->integer('id') ?: ($mode === 'kelas' ? $daftarKelas->first()?->id : $daftarGuru->first()?->id);

        $jadwal = Jadwal::with(['pengampu.guru', 'pengampu.mapel', 'pengampu.kelas'])
            ->whereHas('pengampu', fn ($q) => $q->tahunAktif()
                ->where($mode === 'kelas' ? 'kelas_id' : 'guru_id', $pilihanId))
            ->orderBy('jam_mulai')
            ->get()
            ->groupBy('hari');

        return view('operator.jadwal.index', compact('mode', 'daftarKelas', 'daftarGuru', 'pilihanId', 'jadwal'));
    }

    public function create(Request $request): View
    {
        return view('operator.jadwal.form', [
            'jadwal' => new Jadwal(['hari' => $request->input('hari', 'Senin')]),
            'daftarPengampu' => $this->daftarPengampu(),
            'kelasId' => $request->integer('kelas_id'),
        ]);
    }

    public function store(JadwalRequest $request): RedirectResponse
    {
        $jadwal = Jadwal::create($request->validated());
        $jadwal->load('pengampu.kelas', 'pengampu.mapel');
        ActivityLogger::log('tambah_jadwal', "Menambah jadwal {$jadwal->pengampu->mapel->nama_mapel} {$jadwal->pengampu->kelas->nama_kelas} {$jadwal->hari} {$jadwal->jam}");

        return redirect()->route('operator.jadwal.index', ['mode' => 'kelas', 'id' => $jadwal->pengampu->kelas_id])
            ->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(Jadwal $jadwal): View
    {
        return view('operator.jadwal.form', [
            'jadwal' => $jadwal,
            'daftarPengampu' => $this->daftarPengampu(),
            'kelasId' => null,
        ]);
    }

    public function update(JadwalRequest $request, Jadwal $jadwal): RedirectResponse
    {
        $jadwal->update($request->validated());
        ActivityLogger::log('ubah_jadwal', "Mengubah jadwal #{$jadwal->id}");

        return redirect()->route('operator.jadwal.index', ['mode' => 'kelas', 'id' => $jadwal->pengampu->kelas_id])
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Jadwal $jadwal): RedirectResponse
    {
        $jadwal->delete();
        ActivityLogger::log('hapus_jadwal', "Menghapus jadwal #{$jadwal->id}");

        return back()->with('success', 'Jadwal berhasil dihapus.');
    }

    private function daftarPengampu()
    {
        return Pengampu::with(['guru', 'mapel', 'kelas'])
            ->tahunAktif()
            ->get()
            ->sortBy(fn ($p) => $p->kelas->nama_kelas.' '.$p->mapel->nama_mapel)
            ->values();
    }
}
