<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\PresensiRequest;
use App\Models\Pengampu;
use App\Models\Presensi;
use App\Services\ActivityLogger;
use App\Services\PresensiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PresensiController extends Controller
{
    public function __construct(private PresensiService $service) {}

    /** Daftar pertemuan + rekap kehadiran per siswa. */
    public function index(Pengampu $pengampu): View
    {
        return view('guru.presensi.index', [
            'pengampu' => $pengampu,
            'pertemuan' => $this->service->daftarPertemuan($pengampu),
            'rekap' => $this->service->rekap($pengampu),
        ]);
    }

    /** Form input/ubah presensi satu pertemuan (?pertemuan=n). */
    public function form(Request $request, Pengampu $pengampu): View
    {
        // Pertemuan ke tidak bisa ditentukan guru — sistem hitung otomatis.
        $pertemuanMax = (int) Presensi::where('pengampu_id', $pengampu->id)->max('pertemuan_ke');
        $pertemuan = $request->integer('pertemuan') ?: $pertemuanMax + 1;

        $tanggal = Presensi::where('pengampu_id', $pengampu->id)->where('pertemuan_ke', $pertemuan)->value('tanggal')
            ?? now();
        $tanggal = $request->input('tanggal', Carbon::parse($tanggal)->toDateString());

        $catatan = Presensi::where('pengampu_id', $pengampu->id)->where('pertemuan_ke', $pertemuan)->value('catatan');

        $siswa = $pengampu->daftarSiswa();
        $status = $this->service->statusAwal($pengampu, $pertemuan, $tanggal, $siswa);
        $izin = $this->service->izinDisetujui($siswa->pluck('id')->all(), $tanggal);
        $presensiTersimpan = Presensi::where('pengampu_id', $pengampu->id)->where('pertemuan_ke', $pertemuan)->get()->keyBy('siswa_id');

        return view('guru.presensi.form', compact('pengampu', 'pertemuan', 'tanggal', 'siswa', 'status', 'izin', 'catatan', 'presensiTersimpan'));
    }

    public function store(PresensiRequest $request, Pengampu $pengampu): RedirectResponse
    {
        $data = $request->validated();
        $this->service->simpan($pengampu, $data['pertemuan_ke'], $data['tanggal'], $data['status'], $data['catatan'] ?? null);

        ActivityLogger::log('isi_presensi', "Mengisi presensi pertemuan {$data['pertemuan_ke']} ({$pengampu->label})");

        return redirect()->route('guru.presensi.index', $pengampu)->with('success', "Presensi pertemuan {$data['pertemuan_ke']} tersimpan.");
    }

    public function validasi(Pengampu $pengampu, int $pertemuan): RedirectResponse
    {
        $jumlah = $this->service->validasiPertemuan($pengampu, $pertemuan);

        return back()->with('success', "{$jumlah} presensi pertemuan {$pertemuan} berhasil divalidasi.");
    }
}
