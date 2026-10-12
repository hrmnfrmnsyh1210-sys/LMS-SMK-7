<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\AiGagalException;
use App\Exports\LaporanExport;
use App\Services\ActivityLogger;
use App\Services\AsistenAiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/** Ekspor PDF/Excel dan analisis AI untuk data laporan dari LaporanService. */
trait MenyajikanLaporan
{
    /**
     * @param  array{judul: string, subjudul: string, info: array<string, string>, kolom: list<string>, baris: list<list<mixed>>, ringkasan: array<string, string>}  $laporan
     */
    protected function unduhLaporan(array $laporan, string $format): Response
    {
        $nama = Str::slug($laporan['judul'].' '.$laporan['subjudul'], '_');

        ActivityLogger::log('ekspor_laporan', "{$laporan['judul']} ({$laporan['subjudul']}) — ".strtoupper($format));

        if ($format === 'excel') {
            return Excel::download(new LaporanExport($laporan), $nama.'.xlsx');
        }

        return Pdf::loadView('laporan.pdf', ['laporan' => $laporan, 'pencetak' => request()->user()])
            ->setPaper('a4', count($laporan['kolom']) > 9 ? 'landscape' : 'portrait')
            ->download($nama.'.pdf');
    }

    /**
     * @param  array{judul: string, subjudul: string, info: array<string, string>, kolom: list<string>, baris: list<list<mixed>>, ringkasan: array<string, string>}  $laporan
     */
    protected function analisisLaporan(array $laporan, AsistenAiService $ai): JsonResponse
    {
        if ($laporan['baris'] === []) {
            return response()->json(['pesan' => 'Belum ada data untuk dianalisis.'], 422);
        }

        try {
            $teks = $ai->analisisLaporan($laporan, request()->user()->label_role);
        } catch (AiGagalException $e) {
            return response()->json(['pesan' => $e->getMessage()], 503);
        }

        return response()->json(['html' => $ai->html($teks)]);
    }
}
