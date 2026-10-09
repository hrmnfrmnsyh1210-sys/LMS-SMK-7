<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\JurusanRequest;
use App\Models\Jurusan;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JurusanController extends Controller
{
    public function index(Request $request): View
    {
        $cari = $request->string('q')->trim()->value();

        $jurusan = Jurusan::withCount(['kelas' => fn ($q) => $q->tahunAktif()])
            ->when($cari, fn ($q) => $q->where(fn ($q) => $q
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('kode', 'like', "%{$cari}%")))
            ->orderBy('kode')
            ->paginate(config('lms.per_page'))
            ->withQueryString();

        return view('operator.jurusan.index', compact('jurusan', 'cari'));
    }

    public function create(): View
    {
        return view('operator.jurusan.form', ['jurusan' => new Jurusan]);
    }

    public function store(JurusanRequest $request): RedirectResponse
    {
        $jurusan = Jurusan::create($request->validated());
        ActivityLogger::log('tambah_jurusan', "Menambah jurusan {$jurusan->nama}");

        return redirect()->route('operator.jurusan.index')->with('success', 'Jurusan berhasil ditambahkan.');
    }

    public function edit(Jurusan $jurusan): View
    {
        return view('operator.jurusan.form', compact('jurusan'));
    }

    public function update(JurusanRequest $request, Jurusan $jurusan): RedirectResponse
    {
        $jurusan->update($request->validated());
        ActivityLogger::log('ubah_jurusan', "Mengubah jurusan {$jurusan->nama}");

        return redirect()->route('operator.jurusan.index')->with('success', 'Jurusan berhasil diperbarui.');
    }

    public function destroy(Jurusan $jurusan): RedirectResponse
    {
        if ($jurusan->kelas()->exists()) {
            return back()->with('error', "Jurusan {$jurusan->nama} tidak dapat dihapus karena masih dipakai oleh kelas.");
        }

        $nama = $jurusan->nama;
        $jurusan->delete();
        ActivityLogger::log('hapus_jurusan', "Menghapus jurusan {$nama}");

        return back()->with('success', 'Jurusan berhasil dihapus.');
    }
}
