<?php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Services\MonitoringSekolahService;
use App\Services\RekapKelasService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Monitoring keaktifan guru dan capaian kelas (baca-saja). */
class MonitoringController extends Controller
{
    public function __construct(private MonitoringSekolahService $monitoring) {}

    public function guru(Request $request): View
    {
        $cari = $request->string('q')->trim()->value();

        return view('kepsek.monitoring.guru', ['rekap' => $this->monitoring->rekapGuru($cari), 'cari' => $cari]);
    }

    public function kelas(): View
    {
        return view('kepsek.monitoring.kelas', ['rekap' => $this->monitoring->rekapKelas(), 'kkm' => (float) pengaturan('kkm', 75)]);
    }

    public function kelasShow(Kelas $kelas, RekapKelasService $rekap): View
    {
        return view('kepsek.monitoring.kelas-show', $rekap->rekap($kelas));
    }
}
