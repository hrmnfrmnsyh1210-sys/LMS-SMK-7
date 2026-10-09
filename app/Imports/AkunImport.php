<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;

/**
 * Dasar import akun: validasi per baris, baris valid langsung disimpan,
 * baris gagal dikumpulkan beserta alasannya.
 */
abstract class AkunImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $berhasil = 0;

    /** @var list<array{baris:int, data:string, alasan:list<string>}> */
    public array $gagal = [];

    /** Kolom template (urutan heading). */
    abstract public static function kolom(): array;

    /** Contoh baris pada template. */
    abstract public static function contoh(): array;

    abstract protected function rules(array $row): array;

    abstract protected function simpan(array $row): void;

    abstract protected function ringkas(array $row): string;

    protected function attributes(): array
    {
        return [];
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            // Baris 1 = heading, data mulai baris 2.
            $nomorBaris = $index + 2;
            $data = $this->normalisasi($row->toArray());

            $validator = Validator::make($data, $this->rules($data), [], $this->attributes());
            if ($validator->fails()) {
                $this->catatGagal($nomorBaris, $data, $validator->errors()->all());

                continue;
            }

            try {
                $this->simpan($validator->validated());
                $this->berhasil++;
            } catch (Throwable $e) {
                report($e);
                $this->catatGagal($nomorBaris, $data, ['Gagal disimpan: '.$e->getMessage()]);
            }
        }
    }

    protected function normalisasi(array $row): array
    {
        $hasil = [];
        foreach (static::kolom() as $kolom) {
            $nilai = $row[$kolom] ?? null;
            $hasil[$kolom] = is_string($nilai) ? trim($nilai) : $nilai;
            if ($hasil[$kolom] === '') {
                $hasil[$kolom] = null;
            }
            // NIS/NIP/NISN sering terbaca sebagai angka oleh Excel.
            if ((is_int($hasil[$kolom]) || is_float($hasil[$kolom])) && $kolom !== 'tgl_lahir') {
                $hasil[$kolom] = is_float($hasil[$kolom]) ? sprintf('%.0f', $hasil[$kolom]) : (string) $hasil[$kolom];
            }
        }

        if (isset($hasil['jenis_kelamin'])) {
            $hasil['jenis_kelamin'] = strtoupper(substr((string) $hasil['jenis_kelamin'], 0, 1));
        }

        if (isset($hasil['tgl_lahir']) && is_numeric($hasil['tgl_lahir'])) {
            $hasil['tgl_lahir'] = Date::excelToDateTimeObject((float) $hasil['tgl_lahir'])->format('Y-m-d');
        }

        return $hasil;
    }

    private function catatGagal(int $baris, array $data, array $alasan): void
    {
        $this->gagal[] = ['baris' => $baris, 'data' => $this->ringkas($data), 'alasan' => $alasan];
    }
}
