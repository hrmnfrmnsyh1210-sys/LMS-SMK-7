<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Laporan generik dari LaporanService: judul, info, tabel, lalu ringkasan di bawahnya. */
class LaporanExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param  array{judul: string, subjudul: string, info: array<string, string>, kolom: list<string>, baris: list<list<mixed>>, ringkasan: array<string, string>}  $laporan
     */
    public function __construct(private array $laporan) {}

    public function array(): array
    {
        $baris = array_map(fn ($b) => array_map(fn ($sel) => $sel ?? '-', $b), $this->laporan['baris']);

        $baris[] = [];
        $baris[] = ['Ringkasan'];
        foreach ($this->laporan['ringkasan'] as $label => $nilai) {
            $baris[] = [$label, $nilai];
        }

        return $baris;
    }

    public function headings(): array
    {
        $info = collect($this->laporan['info'])->map(fn ($v, $k) => "{$k}: {$v}")->implode(' · ');

        return [
            [$this->laporan['judul']],
            [pengaturan('nama_sekolah').' · '.$this->laporan['subjudul'].($info ? ' · '.$info : '')],
            $this->laporan['kolom'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $ringkasan = 3 + count($this->laporan['baris']) + 2;

        return [1 => ['font' => ['bold' => true, 'size' => 13]], 3 => ['font' => ['bold' => true]], $ringkasan => ['font' => ['bold' => true]]];
    }

    public function title(): string
    {
        return 'Laporan';
    }
}
