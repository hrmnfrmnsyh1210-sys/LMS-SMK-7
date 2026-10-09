<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\PengampuRequest;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Pengampu;
use App\Models\TahunAjaran;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Plotting guru – mapel – kelas pada tahun ajaran aktif. */
class PengampuController extends Controller
{
    public function index(Request $request): View
    {
        $cari = $request->string('q')->trim()->value();

        $pengampu = Pengampu::with(['guru', 'mapel', 'kelas'])
            ->withCount('jadwal')
            ->tahunAktif()
            ->when($cari, fn ($q) => $q->where(fn ($q) => $q
                ->whereHas('guru', fn ($q) => $q->where('nama', 'like', "%{$cari}%"))
                ->orWhereHas('mapel', fn ($q) => $q->where('nama_mapel', 'like', "%{$cari}%"))))
            ->when($request->filled('kelas_id'), fn ($q) => $q->where('kelas_id', $request->kelas_id))
            ->when($request->filled('guru_id'), fn ($q) => $q->where('guru_id', $request->guru_id))
            ->join('kelas', 'kelas.id', '=', 'pengampu.kelas_id')
            ->orderBy('kelas.nama_kelas')
            ->select('pengampu.*')
            ->paginate(config('lms.per_page'))
            ->withQueryString();

        return view('operator.pengampu.index', ['pengampu' => $pengampu, 'cari' => $cari] + $this->dataForm());
    }

    public function create(): View
    {
        return view('operator.pengampu.form', ['pengampu' => new Pengampu] + $this->dataForm());
    }

    public function store(PengampuRequest $request): RedirectResponse
    {
        abort_unless(TahunAjaran::aktifId(), 422, 'Belum ada tahun ajaran aktif.');

        $pengampu = Pengampu::create($request->validated() + ['tahun_ajaran_id' => TahunAjaran::aktifId()]);
        $pengampu->load(['guru', 'mapel', 'kelas']);
        ActivityLogger::log('tambah_pengampu', "Plotting {$pengampu->guru->nama} – {$pengampu->mapel->nama_mapel} – {$pengampu->kelas->nama_kelas}");

        return redirect()->route('operator.pengampu.index')->with('success', 'Pengampu berhasil ditambahkan.');
    }

    public function edit(Pengampu $pengampu): View
    {
        return view('operator.pengampu.form', ['pengampu' => $pengampu] + $this->dataForm());
    }

    public function update(PengampuRequest $request, Pengampu $pengampu): RedirectResponse
    {
        $pengampu->update($request->validated());
        $pengampu->load(['guru', 'mapel', 'kelas']);
        ActivityLogger::log('ubah_pengampu', "Mengubah plotting {$pengampu->mapel->nama_mapel} {$pengampu->kelas->nama_kelas} → {$pengampu->guru->nama}");

        return redirect()->route('operator.pengampu.index')->with('success', 'Pengampu berhasil diperbarui.');
    }

    public function destroy(Pengampu $pengampu): RedirectResponse
    {
        if ($pengampu->sedangDipakai()) {
            return back()->with('error', 'Pengampu tidak dapat dihapus karena sudah memiliki materi/tugas/kuis/presensi/nilai. Ganti gurunya saja melalui menu ubah.');
        }

        $pengampu->delete();
        ActivityLogger::log('hapus_pengampu', "Menghapus pengampu #{$pengampu->id}");

        return back()->with('success', 'Pengampu berhasil dihapus.');
    }

    private function dataForm(): array
    {
        return [
            'daftarGuru' => Guru::whereHas('user', fn ($q) => $q->where('status', 'aktif'))->orderBy('nama')->get(),
            'daftarMapel' => Mapel::orderBy('nama_mapel')->get(),
            'daftarKelas' => Kelas::tahunAktif()->orderBy('nama_kelas')->get(),
        ];
    }
}
