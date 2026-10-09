<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\KepsekRequest;
use App\Models\Kepsek;
use App\Services\ActivityLogger;
use App\Services\AkunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KepsekController extends Controller
{
    private const FIELD_PROFIL = ['nip', 'nama', 'no_hp', 'jenis_kelamin'];

    public function __construct(private AkunService $akun) {}

    public function index(): View
    {
        $kepsek = Kepsek::with('user')->orderBy('nama')->paginate(config('lms.per_page'));

        return view('operator.kepsek.index', compact('kepsek'));
    }

    public function create(): View
    {
        return view('operator.kepsek.form', ['kepsek' => new Kepsek]);
    }

    public function store(KepsekRequest $request): RedirectResponse
    {
        $kepsek = $this->akun->buatKepsek($request->validated());
        ActivityLogger::log('tambah_kepsek', "Menambah akun kepala sekolah {$kepsek->nama}");

        return redirect()->route('operator.kepsek.index')
            ->with('success', "Akun kepala sekolah berhasil dibuat. Password awal: {$this->akun->passwordDefault($kepsek->user)}.");
    }

    public function edit(Kepsek $kepsek): View
    {
        $kepsek->load('user');

        return view('operator.kepsek.form', compact('kepsek'));
    }

    public function update(KepsekRequest $request, Kepsek $kepsek): RedirectResponse
    {
        $this->akun->perbarui($kepsek, $request->validated(), self::FIELD_PROFIL);
        ActivityLogger::log('ubah_kepsek', "Mengubah data kepala sekolah {$kepsek->nama}");

        return redirect()->route('operator.kepsek.index')->with('success', 'Data kepala sekolah berhasil diperbarui.');
    }

    public function destroy(Kepsek $kepsek): RedirectResponse
    {
        $nama = $kepsek->nama;
        $kepsek->user->delete();
        ActivityLogger::log('hapus_kepsek', "Menghapus akun kepala sekolah {$nama}");

        return back()->with('success', "Akun {$nama} berhasil dihapus.");
    }
}
