<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\MapelRequest;
use App\Models\Mapel;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapelController extends Controller
{
    public function index(Request $request): View
    {
        $cari = $request->string('q')->trim()->value();

        $mapel = Mapel::withCount(['pengampu' => fn ($q) => $q->tahunAktif()])
            ->when($cari, fn ($q) => $q->where(fn ($q) => $q
                ->where('nama_mapel', 'like', "%{$cari}%")
                ->orWhere('kode', 'like', "%{$cari}%")))
            ->orderBy('kode')
            ->paginate(config('lms.per_page'))
            ->withQueryString();

        return view('operator.mapel.index', compact('mapel', 'cari'));
    }

    public function create(): View
    {
        return view('operator.mapel.form', ['mapel' => new Mapel]);
    }

    public function store(MapelRequest $request): RedirectResponse
    {
        $mapel = Mapel::create($request->validated());
        ActivityLogger::log('tambah_mapel', "Menambah mapel {$mapel->nama_mapel}");

        return redirect()->route('operator.mapel.index')->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function edit(Mapel $mapel): View
    {
        return view('operator.mapel.form', compact('mapel'));
    }

    public function update(MapelRequest $request, Mapel $mapel): RedirectResponse
    {
        $mapel->update($request->validated());
        ActivityLogger::log('ubah_mapel', "Mengubah mapel {$mapel->nama_mapel}");

        return redirect()->route('operator.mapel.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(Mapel $mapel): RedirectResponse
    {
        if ($mapel->pengampu()->exists()) {
            return back()->with('error', "Mata pelajaran {$mapel->nama_mapel} tidak dapat dihapus karena sudah memiliki pengampu.");
        }

        $nama = $mapel->nama_mapel;
        $mapel->delete();
        ActivityLogger::log('hapus_mapel', "Menghapus mapel {$nama}");

        return back()->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
