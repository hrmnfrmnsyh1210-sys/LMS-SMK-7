<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\PresensiRequest;
use App\Models\AbsensiGuru;
use App\Models\Jadwal;
use App\Models\Pengampu;
use App\Models\Presensi;
use App\Services\ActivityLogger;
use App\Services\PresensiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PresensiController extends Controller
{
    public function __construct(private PresensiService $service) {}

    /** Daftar pertemuan + rekap kehadiran per siswa. */
    public function index(Pengampu $pengampu): View
    {
        $pertemuanHariIni = Presensi::where('pengampu_id', $pengampu->id)
            ->whereDate('tanggal', today())
            ->value('pertemuan_ke');

        $pertemuanMax = (int) Presensi::where('pengampu_id', $pengampu->id)->max('pertemuan_ke');

        return view('guru.presensi.index', [
            'pengampu' => $pengampu,
            'pertemuan' => $this->service->daftarPertemuan($pengampu),
            'rekap' => $this->service->rekap($pengampu),
            'pertemuanHariIni' => $pertemuanHariIni,
            'pertemuanOtomatis' => $pertemuanHariIni ?: ($pertemuanMax + 1),
        ]);
    }

    /** Form input/ubah presensi satu pertemuan (?pertemuan=n). */
    public function form(Request $request, Pengampu $pengampu): View
    {
        // Pertemuan ke otomatis: utamakan pertemuan hari ini bila ada, atau pertemuan berikutnya (max + 1).
        $pertemuanHariIni = Presensi::where('pengampu_id', $pengampu->id)
            ->whereDate('tanggal', today())
            ->value('pertemuan_ke');

        $pertemuanMax = (int) Presensi::where('pengampu_id', $pengampu->id)->max('pertemuan_ke');

        if ($request->has('pertemuan')) {
            $pertemuan = $request->integer('pertemuan');
        } elseif ($pertemuanHariIni) {
            $pertemuan = $pertemuanHariIni;
        } else {
            $pertemuan = $pertemuanMax + 1;
        }

        $tanggal = Presensi::where('pengampu_id', $pengampu->id)->where('pertemuan_ke', $pertemuan)->value('tanggal')
            ?? today()->toDateString();
        $tanggal = $request->input('tanggal', Carbon::parse($tanggal)->toDateString());

        $catatan = Presensi::where('pengampu_id', $pengampu->id)->where('pertemuan_ke', $pertemuan)->value('catatan');
        $materi = Presensi::where('pengampu_id', $pengampu->id)->where('pertemuan_ke', $pertemuan)->value('materi');
        $tugas = Presensi::where('pengampu_id', $pengampu->id)->where('pertemuan_ke', $pertemuan)->value('tugas');
        $dokumentasi = Presensi::where('pengampu_id', $pengampu->id)->where('pertemuan_ke', $pertemuan)->value('dokumentasi');

        // Jika materi/tugas/dokumentasi belum terisi, coba ambil dari absensi guru mengajar hari ini.
        if (! $materi || ! $tugas || ! $dokumentasi) {
            $hariIni = Jadwal::hariDari(today());
            if ($hariIni) {
                $absensiGuru = AbsensiGuru::where('guru_id', $pengampu->guru_id)
                    ->whereHas('jadwal', fn ($q) => $q->where('pengampu_id', $pengampu->id)->where('hari', $hariIni))
                    ->whereDate('tanggal', today())
                    ->first();
                $materi ??= $absensiGuru?->materi;
                $tugas ??= $absensiGuru?->aktivitas;
                $dokumentasi ??= $absensiGuru?->dokumentasi;
            }
        }

        $siswa = $pengampu->daftarSiswa();
        $status = $this->service->statusAwal($pengampu, $pertemuan, $tanggal, $siswa);
        $izin = $this->service->izinDisetujui($siswa->pluck('id')->all(), $tanggal);
        $presensiTersimpan = Presensi::where('pengampu_id', $pengampu->id)->where('pertemuan_ke', $pertemuan)->get()->keyBy('siswa_id');

        return view('guru.presensi.form', compact('pengampu', 'pertemuan', 'tanggal', 'siswa', 'status', 'izin', 'catatan', 'materi', 'tugas', 'dokumentasi', 'presensiTersimpan'));
    }

    public function store(PresensiRequest $request, Pengampu $pengampu): RedirectResponse
    {
        $data = $request->validated();

        $pathLama = Presensi::where('pengampu_id', $pengampu->id)
            ->where('pertemuan_ke', $data['pertemuan_ke'])
            ->value('dokumentasi');

        $dokumentasi = $pathLama;

        if ($request->hasFile('dokumentasi')) {
            if ($pathLama) {
                Storage::disk('public')->delete($pathLama);
            }
            $dokumentasi = $request->file('dokumentasi')->store("jurnal/{$pengampu->id}", 'public');
        } elseif ($request->boolean('hapus_dokumentasi')) {
            if ($pathLama) {
                Storage::disk('public')->delete($pathLama);
            }
            $dokumentasi = '';
        }

        $this->service->simpan(
            $pengampu,
            $data['pertemuan_ke'],
            $data['tanggal'],
            $data['status'],
            $data['catatan'] ?? null,
            $data['materi'] ?? null,
            $data['tugas'] ?? null,
            $dokumentasi
        );

        ActivityLogger::log('isi_presensi', "Mengisi presensi & jurnal pertemuan {$data['pertemuan_ke']} ({$pengampu->label})");

        return redirect()->route('guru.presensi.index', $pengampu)->with('success', "Presensi & jurnal pertemuan {$data['pertemuan_ke']} tersimpan.");
    }
}
