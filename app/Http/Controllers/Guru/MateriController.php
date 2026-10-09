<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guru\Concerns\KelolaBerkasDanNotifikasi;
use App\Http\Requests\Guru\MateriRequest;
use App\Models\Materi;
use App\Models\Pengampu;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MateriController extends Controller
{
    use KelolaBerkasDanNotifikasi;

    public function index(Pengampu $pengampu): View
    {
        $materi = $pengampu->materi()->orderBy('pertemuan_ke')->orderBy('created_at')->get()->groupBy('pertemuan_ke');

        return view('guru.materi.index', compact('pengampu', 'materi'));
    }

    public function create(Pengampu $pengampu): View
    {
        $pertemuan = (int) $pengampu->materi()->max('pertemuan_ke') + 1;

        return view('guru.materi.form', ['pengampu' => $pengampu, 'materi' => new Materi(['pertemuan_ke' => $pertemuan])]);
    }

    public function store(MateriRequest $request, Pengampu $pengampu): RedirectResponse
    {
        $data = $request->safe()->except(['file', 'hapus_file']);
        $data['file_path'] = $this->prosesBerkas($request, null, "materi/{$pengampu->id}");

        if (! $data['file_path'] && blank($data['tautan'] ?? null) && blank($data['deskripsi'] ?? null)) {
            return back()->withInput()->withErrors(['file' => 'Isi minimal salah satu: file, tautan, atau deskripsi materi.']);
        }

        $materi = $pengampu->materi()->create($data);

        ActivityLogger::log('tambah_materi', "Menambah materi \"{$materi->judul}\" ({$pengampu->label})");
        $this->beriTahuSiswa($pengampu, "Materi baru {$pengampu->mapel->nama_mapel}: {$materi->judul}");

        return redirect()->route('guru.materi.index', $pengampu)->with('success', 'Materi berhasil ditambahkan.');
    }

    public function edit(Pengampu $pengampu, Materi $materi): View
    {
        return view('guru.materi.form', compact('pengampu', 'materi'));
    }

    public function update(MateriRequest $request, Pengampu $pengampu, Materi $materi): RedirectResponse
    {
        $data = $request->safe()->except(['file', 'hapus_file']);
        $data['file_path'] = $this->prosesBerkas($request, $materi->file_path, "materi/{$pengampu->id}");

        $materi->update($data);
        ActivityLogger::log('ubah_materi', "Mengubah materi \"{$materi->judul}\" ({$pengampu->label})");

        return redirect()->route('guru.materi.index', $pengampu)->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroy(Pengampu $pengampu, Materi $materi): RedirectResponse
    {
        $this->hapusBerkas($materi->file_path);
        $materi->delete();
        ActivityLogger::log('hapus_materi', "Menghapus materi \"{$materi->judul}\" ({$pengampu->label})");

        return back()->with('success', 'Materi berhasil dihapus.');
    }
}
