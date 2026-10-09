<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\KelasRequest;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function index(Request $request): View
    {
        $cari = $request->string('q')->trim()->value();
        $tahunId = $request->integer('tahun_ajaran_id') ?: TahunAjaran::aktifId();

        $kelas = Kelas::with(['jurusan', 'waliKelas'])
            ->withCount(['siswa', 'pengampu'])
            ->where('tahun_ajaran_id', $tahunId)
            ->when($cari, fn ($q) => $q->where('nama_kelas', 'like', "%{$cari}%"))
            ->when($request->filled('tingkat'), fn ($q) => $q->where('tingkat', $request->tingkat))
            ->when($request->filled('jurusan_id'), fn ($q) => $q->where('jurusan_id', $request->jurusan_id))
            ->orderByRaw("FIELD(tingkat, 'X', 'XI', 'XII')")
            ->orderBy('nama_kelas')
            ->paginate(config('lms.per_page'))
            ->withQueryString();

        return view('operator.kelas.index', [
            'kelas' => $kelas,
            'cari' => $cari,
            'tahunId' => $tahunId,
            'daftarTahun' => TahunAjaran::orderByDesc('nama')->orderByDesc('semester')->get(),
            'daftarJurusan' => Jurusan::orderBy('kode')->get(),
        ]);
    }

    public function create(): View
    {
        return view('operator.kelas.form', ['kelas' => new Kelas] + $this->dataForm());
    }

    public function store(KelasRequest $request): RedirectResponse
    {
        $kelas = Kelas::create($request->validated());
        ActivityLogger::log('tambah_kelas', "Menambah kelas {$kelas->nama_kelas}");

        return redirect()->route('operator.kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function edit(Kelas $kelas): View
    {
        return view('operator.kelas.form', ['kelas' => $kelas] + $this->dataForm());
    }

    public function update(KelasRequest $request, Kelas $kelas): RedirectResponse
    {
        $kelas->update($request->validated());
        ActivityLogger::log('ubah_kelas', "Mengubah kelas {$kelas->nama_kelas}");

        return redirect()->route('operator.kelas.index', ['tahun_ajaran_id' => $kelas->tahun_ajaran_id])
            ->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        if ($kelas->sedangDipakai()) {
            return back()->with('error', "Kelas {$kelas->nama_kelas} tidak dapat dihapus karena sudah memiliki siswa atau pengampu.");
        }

        $nama = $kelas->nama_kelas;
        $kelas->delete();
        ActivityLogger::log('hapus_kelas', "Menghapus kelas {$nama}");

        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    private function dataForm(): array
    {
        return [
            'daftarJurusan' => Jurusan::orderBy('kode')->get(),
            'daftarGuru' => Guru::orderBy('nama')->get(),
            'daftarTahun' => TahunAjaran::orderByDesc('nama')->orderByDesc('semester')->get(),
        ];
    }
}
