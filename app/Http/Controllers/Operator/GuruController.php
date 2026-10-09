<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\GuruRequest;
use App\Models\Guru;
use App\Services\ActivityLogger;
use App\Services\AkunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuruController extends Controller
{
    private const FIELD_PROFIL = ['nip', 'nama', 'jenis_kelamin', 'no_hp', 'alamat'];

    public function __construct(private AkunService $akun) {}

    public function index(Request $request): View
    {
        $cari = $request->string('q')->trim()->value();

        $guru = Guru::with('user')
            ->withCount(['pengampu' => fn ($q) => $q->tahunAktif()])
            ->when($cari, fn ($q) => $q->where(fn ($q) => $q
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('nip', 'like', "%{$cari}%")
                ->orWhereHas('user', fn ($q) => $q->where('username', 'like', "%{$cari}%"))))
            ->when($request->filled('status'), fn ($q) => $q->whereHas('user', fn ($q) => $q->where('status', $request->status)))
            ->orderBy('nama')
            ->paginate(config('lms.per_page'))
            ->withQueryString();

        return view('operator.guru.index', compact('guru', 'cari'));
    }

    public function create(): View
    {
        return view('operator.guru.form', ['guru' => new Guru]);
    }

    public function store(GuruRequest $request): RedirectResponse
    {
        $guru = $this->akun->buatGuru($request->validated());
        ActivityLogger::log('tambah_guru', "Menambah guru {$guru->nama} ({$guru->user->username})");

        return redirect()->route('operator.guru.index')
            ->with('success', "Guru {$guru->nama} berhasil ditambahkan. Username: {$guru->user->username}, password awal: {$this->akun->passwordDefault($guru->user)}.");
    }

    public function edit(Guru $guru): View
    {
        $guru->load('user');

        return view('operator.guru.form', compact('guru'));
    }

    public function update(GuruRequest $request, Guru $guru): RedirectResponse
    {
        $data = $request->validated();
        $data['username'] = $data['username'] ?? null ?: $guru->user->username;

        $this->akun->perbarui($guru, $data, self::FIELD_PROFIL);
        ActivityLogger::log('ubah_guru', "Mengubah data guru {$guru->nama}");

        return redirect()->route('operator.guru.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy(Guru $guru): RedirectResponse
    {
        if ($guru->pengampu()->exists() || $guru->kelasWali()->exists()) {
            return back()->with('error', "Guru {$guru->nama} tidak dapat dihapus karena sudah memiliki data mengajar atau menjadi wali kelas. Nonaktifkan akunnya saja.");
        }

        $nama = $guru->nama;
        $guru->user->delete();
        ActivityLogger::log('hapus_guru', "Menghapus guru {$nama}");

        return back()->with('success', "Guru {$nama} berhasil dihapus.");
    }
}
