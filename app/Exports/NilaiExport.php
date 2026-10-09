<?php

namespace App\Exports;

use App\Models\Nilai;
use App\Models\Pengampu;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Rekap nilai satu pengampu. */
class NilaiExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /** @param Collection<int, Nilai> $nilai */
    public function __construct(private Pengampu $pengampu, private Collection $nilai) {}

    public function array(): array
    {
        return $this->nilai->values()->map(fn (Nilai $n, $i) => [
            $i + 1,
            $n->siswa->nis,
            $n->siswa->nama,
            $n->nilai_tugas,
            $n->nilai_kuis,
            $n->nilai_uts,
            $n->nilai_uas,
            $n->nilai_akhir,
        ])->all();
    }

    public function headings(): array
    {
        $b = $this->pengampu->bobot();

        return [
            ["Rekap Nilai {$this->pengampu->mapel->nama_mapel} — {$this->pengampu->kelas->nama_kelas} ({$this->pengampu->tahunAjaran->label})"],
            ["Guru: {$this->pengampu->guru->nama} · Bobot: tugas {$b->bobot_tugas}%, kuis {$b->bobot_kuis}%, UTS {$b->bobot_uts}%, UAS {$b->bobot_uas}%"],
            ['No', 'NIS', 'Nama', 'Tugas', 'Kuis', 'UTS', 'UAS', 'Nilai Akhir'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'size' => 13]], 3 => ['font' => ['bold' => true]]];
    }

    public function title(): string
    {
        return 'Nilai';
    }
}
