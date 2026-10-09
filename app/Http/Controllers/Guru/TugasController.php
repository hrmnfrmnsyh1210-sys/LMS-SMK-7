<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guru\Concerns\KelolaBerkasDanNotifikasi;
use App\Http\Requests\Guru\NilaiPengumpulanRequest;
use App\Http\Requests\Guru\TugasRequest;
use App\Models\Pengampu;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use App\Notifications\Pemberitahuan;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TugasController extends Controller
{
    use KelolaBerkasDanNotifikasi;

    public function index(Pengampu $pengampu): View
    {
        $tugas = $pengampu->tugas()
            ->withCount([
                'pengumpulan',
                'pengumpulan as dinilai_count' => fn ($q) => $q->whereNotNull('nilai'),
            ])
            ->latest('deadline')
            ->get();

        return view('guru.tugas.index', [
            'pengampu' => $pengampu,
            'tugas' => $tugas,
            'jumlahSiswa' => $pengampu->daftarSiswa()->count(),
        ]);
    }

    public function create(Pengampu $pengampu): View
    {
        return view('guru.tugas.form', [
            'pengampu' => $pengampu,
            'tugas' => new Tugas(['nilai_maks' => 100, 'deadline' => now()->addWeek()->setTime(23, 59)]),
        ]);
    }

    public function store(TugasRequest $request, Pengampu $pengampu): RedirectResponse
    {
        $data = $request->safe()->except(['file', 'hapus_file']);
        $data['file_path'] = $this->prosesBerkas($request, null, "tugas/{$pengampu->id}");

        $tugas = $pengampu->tugas()->create($data);

        ActivityLogger::log('tambah_tugas', "Menambah tugas \"{$tugas->judul}\" ({$pengampu->label})");
        $this->beriTahuSiswa($pengampu, "Tugas baru {$pengampu->mapel->nama_mapel}: {$tugas->judul} (deadline ".tanggal_indo($tugas->deadline, false, true).')');

        return redirect()->route('guru.tugas.show', [$pengampu, $tugas])->with('success', 'Tugas berhasil dibuat.');
    }

    /** Daftar pengumpulan: sudah / belum / terlambat. */
    public function show(Request $request, Pengampu $pengampu, Tugas $tugas): View
    {
        $pengumpulan = $tugas->pengumpulan()->get()->keyBy('siswa_id');

        $daftar = $pengampu->daftarSiswa()->map(function ($siswa) use ($pengumpulan, $tugas) {
            $p = $pengumpulan->get($siswa->id);
            $p?->setRelation('tugas', $tugas);

            return [
                'siswa' => $siswa,
                'pengumpulan' => $p,
                'status' => match (true) {
                    ! $p => 'belum',
                    $p->terlambat => 'terlambat',
                    default => 'sudah',
                },
            ];
        });

        $filter = $request->input('status');
        $ringkasan = $daftar->countBy('status');
        if (in_array($filter, ['sudah', 'belum', 'terlambat'], true)) {
            $daftar = $daftar->where('status', $filter);
        }

        return view('guru.tugas.show', compact('pengampu', 'tugas', 'daftar', 'ringkasan', 'filter'));
    }

    public function edit(Pengampu $pengampu, Tugas $tugas): View
    {
        return view('guru.tugas.form', compact('pengampu', 'tugas'));
    }

    public function update(TugasRequest $request, Pengampu $pengampu, Tugas $tugas): RedirectResponse
    {
        $data = $request->safe()->except(['file', 'hapus_file']);
        $data['file_path'] = $this->prosesBerkas($request, $tugas->file_path, "tugas/{$pengampu->id}");

        $tugas->update($data);
        ActivityLogger::log('ubah_tugas', "Mengubah tugas \"{$tugas->judul}\" ({$pengampu->label})");

        return redirect()->route('guru.tugas.show', [$pengampu, $tugas])->with('success', 'Tugas berhasil diperbarui.');
    }

    public function destroy(Pengampu $pengampu, Tugas $tugas): RedirectResponse
    {
        if ($tugas->pengumpulan()->exists()) {
            return back()->with('error', 'Tugas yang sudah memiliki pengumpulan siswa tidak dapat dihapus.');
        }

        $this->hapusBerkas($tugas->file_path);
        $tugas->delete();
        ActivityLogger::log('hapus_tugas', "Menghapus tugas \"{$tugas->judul}\" ({$pengampu->label})");

        return redirect()->route('guru.tugas.index', $pengampu)->with('success', 'Tugas berhasil dihapus.');
    }

    public function nilai(NilaiPengumpulanRequest $request, Pengampu $pengampu, Tugas $tugas, PengumpulanTugas $pengumpulan): RedirectResponse
    {
        $pengumpulan->update($request->validated());

        ActivityLogger::log('nilai_tugas', "Menilai tugas \"{$tugas->judul}\" milik {$pengumpulan->siswa->nama}: {$pengumpulan->nilai}");
        $pengumpulan->siswa->user?->notify(new Pemberitahuan("Tugas \"{$tugas->judul}\" sudah dinilai: {$pengumpulan->nilai}/{$tugas->nilai_maks}"));

        return back()->with('success', "Nilai {$pengumpulan->siswa->nama} tersimpan.");
    }
}
