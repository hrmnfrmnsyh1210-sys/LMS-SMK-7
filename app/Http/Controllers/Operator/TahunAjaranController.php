<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\TahunAjaranRequest;
use App\Models\TahunAjaran;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TahunAjaranController extends Controller
{
    public function index(Request $request): View
    {
        $cari = $request->string('q')->trim()->value();

        $tahunAjaran = TahunAjaran::withCount(['kelas', 'pengampu'])
            ->when($cari, fn ($q) => $q->where('nama', 'like', "%{$cari}%"))
            ->orderByDesc('nama')->orderByDesc('semester')
            ->paginate(config('lms.per_page'))
            ->withQueryString();

        return view('operator.tahun-ajaran.index', compact('tahunAjaran', 'cari'));
    }

    public function create(): View
    {
        return view('operator.tahun-ajaran.form', ['tahunAjaran' => new TahunAjaran]);
    }

    public function store(TahunAjaranRequest $request): RedirectResponse
    {
        $tahunAjaran = TahunAjaran::create($request->validated());

        // Tahun ajaran pertama otomatis aktif.
        if (! TahunAjaran::aktif()) {
            $tahunAjaran->aktifkan();
        }

        ActivityLogger::log('tambah_tahun_ajaran', "Menambah tahun ajaran {$tahunAjaran->label}");

        return redirect()->route('operator.tahun-ajaran.index')->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function edit(TahunAjaran $tahunAjaran): View
    {
        return view('operator.tahun-ajaran.form', compact('tahunAjaran'));
    }

    public function update(TahunAjaranRequest $request, TahunAjaran $tahunAjaran): RedirectResponse
    {
        $tahunAjaran->update($request->validated());
        ActivityLogger::log('ubah_tahun_ajaran', "Mengubah tahun ajaran {$tahunAjaran->label}");

        return redirect()->route('operator.tahun-ajaran.index')->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function destroy(TahunAjaran $tahunAjaran): RedirectResponse
    {
        if ($tahunAjaran->status_aktif) {
            return back()->with('error', 'Tahun ajaran yang sedang aktif tidak dapat dihapus.');
        }

        if ($tahunAjaran->sedangDipakai()) {
            return back()->with('error', "Tahun ajaran {$tahunAjaran->label} tidak dapat dihapus karena sudah memiliki data kelas/pengampu.");
        }

        $label = $tahunAjaran->label;
        $tahunAjaran->delete();
        ActivityLogger::log('hapus_tahun_ajaran', "Menghapus tahun ajaran {$label}");

        return back()->with('success', 'Tahun ajaran berhasil dihapus.');
    }

    public function aktifkan(TahunAjaran $tahunAjaran): RedirectResponse
    {
        $tahunAjaran->aktifkan();
        ActivityLogger::log('aktifkan_tahun_ajaran', "Mengaktifkan tahun ajaran {$tahunAjaran->label}");

        return back()->with('success', "Tahun ajaran {$tahunAjaran->label} sekarang aktif.");
    }
}
