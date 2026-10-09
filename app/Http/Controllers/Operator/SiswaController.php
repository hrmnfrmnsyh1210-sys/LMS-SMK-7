<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\SiswaRequest;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\ActivityLogger;
use App\Services\AkunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SiswaController extends Controller
{
    private const FIELD_PROFIL = ['nis', 'nisn', 'nama', 'jenis_kelamin', 'tgl_lahir', 'alamat', 'nama_ortu'];

    public function __construct(private AkunService $akun) {}

    public function index(Request $request): View
    {
        $tahunId = TahunAjaran::aktifId();
        $cari = $request->string('q')->trim()->value();
        $kelasId = $request->integer('kelas_id');

        $siswa = Siswa::with([
            'user',
            'kelas' => fn ($q) => $q->wherePivot('tahun_ajaran_id', $tahunId),
        ])
            ->when($cari, fn ($q) => $q->where(fn ($q) => $q
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('nis', 'like', "%{$cari}%")
                ->orWhere('nisn', 'like', "%{$cari}%")))
            ->when($kelasId, fn ($q) => $q->whereHas('kelasSiswa', fn ($q) => $q->where('kelas_id', $kelasId)))
            ->when($request->input('kelas_id') === '0', fn ($q) => $q
                ->where('status', 'aktif')
                ->whereDoesntHave('kelasSiswa', fn ($q) => $q->where('tahun_ajaran_id', $tahunId)))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('nama')
            ->paginate(config('lms.per_page'))
            ->withQueryString();

        $daftarKelas = Kelas::tahunAktif()->orderBy('nama_kelas')->get();

        return view('operator.siswa.index', compact('siswa', 'cari', 'daftarKelas'));
    }

    public function create(): View
    {
        return view('operator.siswa.form', [
            'siswa' => new Siswa,
            'kelasSaatIni' => null,
            'daftarKelas' => Kelas::tahunAktif()->orderBy('nama_kelas')->get(),
        ]);
    }

    public function store(SiswaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $siswa = DB::transaction(function () use ($data) {
            $siswa = $this->akun->buatSiswa($data);
            $this->aturKelas($siswa, $data['kelas_id'] ?? null);

            return $siswa;
        });

        ActivityLogger::log('tambah_siswa', "Menambah siswa {$siswa->nama} ({$siswa->nis})");

        return redirect()->route('operator.siswa.index')
            ->with('success', "Siswa {$siswa->nama} berhasil ditambahkan. Username & password awal: {$siswa->nis}.");
    }

    public function edit(Siswa $siswa): View
    {
        $siswa->load('user');

        return view('operator.siswa.form', [
            'siswa' => $siswa,
            'kelasSaatIni' => $siswa->kelasPada(),
            'daftarKelas' => Kelas::tahunAktif()->orderBy('nama_kelas')->get(),
        ]);
    }

    public function update(SiswaRequest $request, Siswa $siswa): RedirectResponse
    {
        $data = $request->validated();
        // Username siswa selalu mengikuti NIS.
        $data['username'] = $data['nis'];

        DB::transaction(function () use ($siswa, $data) {
            $this->akun->perbarui($siswa, $data, self::FIELD_PROFIL);
            $this->aturKelas($siswa, $data['kelas_id'] ?? null);
        });

        ActivityLogger::log('ubah_siswa', "Mengubah data siswa {$siswa->nama}");

        return redirect()->route('operator.siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Siswa $siswa): RedirectResponse
    {
        $dipakai = DB::table('nilai')->where('siswa_id', $siswa->id)->exists()
            || DB::table('presensi')->where('siswa_id', $siswa->id)->exists()
            || DB::table('pengumpulan_tugas')->where('siswa_id', $siswa->id)->exists()
            || DB::table('jawaban_kuis')->where('siswa_id', $siswa->id)->exists();

        if ($dipakai) {
            return back()->with('error', "Siswa {$siswa->nama} tidak dapat dihapus karena sudah memiliki data nilai/presensi/tugas. Nonaktifkan akunnya saja.");
        }

        $nama = $siswa->nama;
        $siswa->user->delete();
        ActivityLogger::log('hapus_siswa', "Menghapus siswa {$nama}");

        return back()->with('success', "Siswa {$nama} berhasil dihapus.");
    }

    /** Tempatkan siswa ke kelas pada tahun ajaran aktif (null = keluarkan). */
    private function aturKelas(Siswa $siswa, ?int $kelasId): void
    {
        $tahunId = TahunAjaran::aktifId();
        if (! $tahunId) {
            return;
        }

        if ($kelasId) {
            KelasSiswa::updateOrCreate(
                ['siswa_id' => $siswa->id, 'tahun_ajaran_id' => $tahunId],
                ['kelas_id' => $kelasId]
            );
        } else {
            KelasSiswa::where('siswa_id', $siswa->id)->where('tahun_ajaran_id', $tahunId)->delete();
        }
    }
}
