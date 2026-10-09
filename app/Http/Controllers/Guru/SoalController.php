<?php

namespace App\Http\Controllers\Guru;

use App\Exports\TemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\SoalRequest;
use App\Imports\SoalImport;
use App\Models\Kuis;
use App\Models\Pengampu;
use App\Models\Soal;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SoalController extends Controller
{
    public function create(Pengampu $pengampu, Kuis $kuis): View|RedirectResponse
    {
        if ($kuis->terkunci()) {
            return $this->tolakTerkunci($pengampu, $kuis);
        }

        return view('guru.soal.form', ['pengampu' => $pengampu, 'kuis' => $kuis, 'soal' => new Soal(['tipe' => 'pg', 'bobot' => 1])]);
    }

    public function store(SoalRequest $request, Pengampu $pengampu, Kuis $kuis): RedirectResponse
    {
        if ($kuis->terkunci()) {
            return $this->tolakTerkunci($pengampu, $kuis);
        }

        $kuis->soal()->create($request->dataSoal());

        $tujuan = $request->boolean('tambah_lagi')
            ? redirect()->route('guru.soal.create', [$pengampu, $kuis])
            : redirect()->route('guru.kuis.show', [$pengampu, $kuis]);

        return $tujuan->with('success', 'Soal berhasil ditambahkan.');
    }

    public function edit(Pengampu $pengampu, Kuis $kuis, Soal $soal): View|RedirectResponse
    {
        if ($kuis->terkunci()) {
            return $this->tolakTerkunci($pengampu, $kuis);
        }

        return view('guru.soal.form', compact('pengampu', 'kuis', 'soal'));
    }

    public function update(SoalRequest $request, Pengampu $pengampu, Kuis $kuis, Soal $soal): RedirectResponse
    {
        if ($kuis->terkunci()) {
            return $this->tolakTerkunci($pengampu, $kuis);
        }

        $soal->update($request->dataSoal());

        return redirect()->route('guru.kuis.show', [$pengampu, $kuis])->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(Pengampu $pengampu, Kuis $kuis, Soal $soal): RedirectResponse
    {
        if ($kuis->terkunci()) {
            return $this->tolakTerkunci($pengampu, $kuis);
        }

        $soal->delete();

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new TemplateExport(SoalImport::kolom(), SoalImport::contoh()), 'template_import_soal.xlsx');
    }

    public function import(Request $request, Pengampu $pengampu, Kuis $kuis): RedirectResponse
    {
        if ($kuis->terkunci()) {
            return $this->tolakTerkunci($pengampu, $kuis);
        }

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120']], [], ['file' => 'file Excel']);

        $import = new SoalImport($kuis);
        Excel::import($import, $request->file('file'));

        ActivityLogger::log('import_soal', "Import soal \"{$kuis->judul}\": {$import->berhasil} berhasil, ".count($import->gagal).' gagal');

        return back()->with([
            'success' => "{$import->berhasil} soal berhasil diimpor.",
            'gagal_import' => $import->gagal,
        ]);
    }

    private function tolakTerkunci(Pengampu $pengampu, Kuis $kuis): RedirectResponse
    {
        return redirect()->route('guru.kuis.show', [$pengampu, $kuis])
            ->with('error', 'Soal tidak dapat diubah karena sudah ada siswa yang mengerjakan kuis ini.');
    }
}
