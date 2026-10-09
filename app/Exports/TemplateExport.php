<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Template import: baris heading + satu baris contoh. Semua kolom berformat teks. */
class TemplateExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles
{
    public function __construct(private array $kolom, private array $contoh) {}

    public function array(): array
    {
        return [$this->contoh];
    }

    public function headings(): array
    {
        return $this->kolom;
    }

    public function columnFormats(): array
    {
        $format = [];
        foreach (array_keys($this->kolom) as $i) {
            $format[chr(65 + $i)] = NumberFormat::FORMAT_TEXT;
        }

        return $format;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
