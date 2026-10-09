<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\OrangTuaRequest;
use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\ActivityLogger;
use App\Services\AkunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Akun orang tua/wali yang memantau satu atau beberapa siswa. */
class OrangTuaController extends Controller
{
    private const FIELD_PROFIL = ['nama', 'hubungan', 'no_hp', 'alamat'];

    public function __construct(private AkunService $akun) {}

    public function index(Request $request): View
    {
        $cari = $request->string('q')->trim()->value();

        $ortu = OrangTua::with(['user', 'anak'])
            ->when($cari, fn ($q) => $q->where(fn ($q) => $q
                ->where('nama', 'like', "%{$cari}%")
                ->orWhereHas('user', fn ($q) => $q->where('username', 'like', "%{$cari}%"))
                ->orWhereHas('anak', fn ($q) => $q->where('nama', 'like', "%{$cari}%")->orWhere('nis', 'like', "%{$cari}%"))))
            ->orderBy('nama')
            ->paginate(config('lms.per_page'))
            ->withQueryString();

        return view('operator.orang-tua.index', [
            'ortu' => $ortu,
            'cari' => $cari,
            'daftarKelas' => Kelas::tahunAktif()->orderBy('nama_kelas')->pluck('nama_kelas', 'id'),
        ]);
    }

    public function create(): View
    {
        return view('operator.orang-tua.form', ['ortu' => new OrangTua(['hubungan' => 'wali']), 'daftarSiswa' => $this->daftarSiswa()]);
    }

    public function store(OrangTuaRequest $request): RedirectResponse
    {
        $ortu = $this->akun->buatOrtu($request->validated());
        ActivityLogger::log('tambah_ortu', "Menambah akun orang tua {$ortu->nama}");

        return redirect()->route('operator.orang-tua.index')
            ->with('success', "Akun orang tua berhasil dibuat. Password awal: {$this->akun->passwordDefault($ortu->user)}.");
    }

    public function edit(OrangTua $orangTua): View
    {
        $orangTua->load(['user', 'anak']);

        return view('operator.orang-tua.form', ['ortu' => $orangTua, 'daftarSiswa' => $this->daftarSiswa()]);
    }

    public function update(OrangTuaRequest $request, OrangTua $orangTua): RedirectResponse
    {
        DB::transaction(function () use ($request, $orangTua) {
            $this->akun->perbarui($orangTua, $request->validated(), self::FIELD_PROFIL);
            $orangTua->anak()->sync($request->validated('siswa_ids'));
        });
        ActivityLogger::log('ubah_ortu', "Mengubah data orang tua {$orangTua->nama}");

        return redirect()->route('operator.orang-tua.index')->with('success', 'Data orang tua berhasil diperbarui.');
    }

    public function destroy(OrangTua $orangTua): RedirectResponse
    {
        $nama = $orangTua->nama;
        $orangTua->user->delete();
        ActivityLogger::log('hapus_ortu', "Menghapus akun orang tua {$nama}");

        return back()->with('success', "Akun {$nama} berhasil dihapus.");
    }

    /** Buat akun orang tua sekaligus untuk siswa satu kelas yang belum memilikinya. */
    public function massal(Request $request): RedirectResponse
    {
        $data = $request->validate(['kelas_id' => ['required', Rule::exists('kelas', 'id')]], [], ['kelas_id' => 'kelas']);
        $kelas = Kelas::findOrFail($data['kelas_id']);

        $jumlah = $this->akun->buatOrtuMassal($kelas);
        ActivityLogger::log('tambah_ortu', "Membuat {$jumlah} akun orang tua massal untuk kelas {$kelas->nama_kelas}");

        return back()->with($jumlah ? 'success' : 'warning', $jumlah
            ? "{$jumlah} akun orang tua dibuat untuk kelas {$kelas->nama_kelas}. Username & password awal: ortu + NIS (mis. ortu26001)."
            : "Semua siswa kelas {$kelas->nama_kelas} sudah memiliki akun orang tua.");
    }

    /**
     * Siswa aktif untuk pilihan anak: id, NIS, nama, dan kelas tahun aktif.
     *
     * @return list<array{id: int, nis: string, nama: string, kelas: ?string}>
     */
    private function daftarSiswa(): array
    {
        return Siswa::with(['kelas' => fn ($q) => $q->wherePivot('tahun_ajaran_id', TahunAjaran::aktifId())])
            ->where('status', 'aktif')
            ->orderBy('nama')
            ->get()
            ->map(fn (Siswa $s) => ['id' => $s->id, 'nis' => $s->nis, 'nama' => $s->nama, 'kelas' => $s->kelas->first()?->nama_kelas])
            ->all();
    }
}
