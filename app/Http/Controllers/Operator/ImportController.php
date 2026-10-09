<?php

namespace App\Http\Controllers\Operator;

use App\Exports\TemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\AkunImport;
use App\Imports\GuruImport;
use App\Imports\SiswaImport;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportController extends Controller
{
    private const JENIS = ['guru' => GuruImport::class, 'siswa' => SiswaImport::class];

    public function index(): View
    {
        return view('operator.import.index', [
            'kolom' => ['guru' => GuruImport::kolom(), 'siswa' => SiswaImport::kolom()],
        ]);
    }

    public function template(string $jenis): BinaryFileResponse
    {
        /** @var class-string<AkunImport> $kelas */
        $kelas = self::JENIS[$jenis] ?? abort(404);

        return Excel::download(new TemplateExport($kelas::kolom(), $kelas::contoh()), "template_import_{$jenis}.xlsx");
    }

    public function store(Request $request, string $jenis): RedirectResponse
    {
        $kelas = self::JENIS[$jenis] ?? abort(404);

        $request->validate(
            ['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120']],
            [],
            ['file' => 'file Excel']
        );

        /** @var AkunImport $import */
        $import = app($kelas);
        Excel::import($import, $request->file('file'));

        ActivityLogger::log("import_{$jenis}", "Import {$jenis}: {$import->berhasil} berhasil, ".count($import->gagal).' gagal');

        return redirect()->route('operator.import.index')->with([
            'hasil_import' => [
                'jenis' => $jenis,
                'berhasil' => $import->berhasil,
                'gagal' => $import->gagal,
            ],
        ]);
    }
}
