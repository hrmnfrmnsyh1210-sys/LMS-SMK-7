<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guru\Concerns\KelolaBerkasDanNotifikasi;
use App\Http\Requests\Guru\KuisRequest;
use App\Models\Kuis;
use App\Models\Pengampu;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KuisController extends Controller
{
    use KelolaBerkasDanNotifikasi;

    public function index(Pengampu $pengampu): View
    {
        $kuis = $pengampu->kuis()
            ->withCount(['soal', 'jawaban as dikerjakan_count' => fn ($q) => $q->whereNotNull('waktu_submit')])
            ->withSum('soal', 'bobot')
            ->latest('waktu_mulai')
            ->get();

        return view('guru.kuis.index', compact('pengampu', 'kuis'));
    }

    public function create(Pengampu $pengampu): View
    {
        return view('guru.kuis.form', ['pengampu' => $pengampu, 'kuis' => new Kuis([
            'jenis' => 'kuis',
            'waktu_mulai' => now()->addDay()->setTime(7, 0),
            'waktu_selesai' => now()->addDay()->setTime(15, 0),
            'durasi_menit' => 60,
            'tampilkan_hasil' => true,
        ])]);
    }

    public function store(KuisRequest $request, Pengampu $pengampu): RedirectResponse
    {
        $kuis = $pengampu->kuis()->create($request->validated());

        ActivityLogger::log('tambah_kuis', "Membuat {$kuis->label_jenis} \"{$kuis->judul}\" ({$pengampu->label})");
        $this->beriTahuSiswa($pengampu, "{$kuis->label_jenis} {$pengampu->mapel->nama_mapel}: {$kuis->judul}, dibuka ".tanggal_indo($kuis->waktu_mulai, false, true));

        return redirect()->route('guru.kuis.show', [$pengampu, $kuis])->with('success', 'Kuis dibuat. Tambahkan soal di bawah ini.');
    }

    /** Detail kuis + bank soal. */
    public function show(Pengampu $pengampu, Kuis $kuis): View
    {
        $kuis->load('soal')->loadCount(['jawaban']);

        return view('guru.kuis.show', compact('pengampu', 'kuis'));
    }

    public function edit(Pengampu $pengampu, Kuis $kuis): View
    {
        return view('guru.kuis.form', compact('pengampu', 'kuis'));
    }

    public function update(KuisRequest $request, Pengampu $pengampu, Kuis $kuis): RedirectResponse
    {
        $kuis->update($request->validated());
        ActivityLogger::log('ubah_kuis', "Mengubah kuis \"{$kuis->judul}\" ({$pengampu->label})");

        return redirect()->route('guru.kuis.show', [$pengampu, $kuis])->with('success', 'Pengaturan kuis diperbarui.');
    }

    public function destroy(Pengampu $pengampu, Kuis $kuis): RedirectResponse
    {
        if ($kuis->terkunci()) {
            return back()->with('error', 'Kuis yang sudah dikerjakan siswa tidak dapat dihapus.');
        }

        $kuis->delete();
        ActivityLogger::log('hapus_kuis', "Menghapus kuis \"{$kuis->judul}\" ({$pengampu->label})");

        return redirect()->route('guru.kuis.index', $pengampu)->with('success', 'Kuis berhasil dihapus.');
    }
}
