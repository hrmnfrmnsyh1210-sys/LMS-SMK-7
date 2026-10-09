<?php

namespace App\Imports;

use App\Models\Kuis;
use App\Models\Soal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/** Import bank soal; baris valid disimpan, baris gagal dilaporkan. */
class SoalImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $berhasil = 0;

    /** @var list<array{baris: int, alasan: list<string>}> */
    public array $gagal = [];

    public function __construct(private Kuis $kuis) {}

    public static function kolom(): array
    {
        return ['tipe', 'pertanyaan', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'kunci_jawaban', 'bobot'];
    }

    public static function contoh(): array
    {
        return ['pg', 'Ibu kota Indonesia adalah ...', 'Bandung', 'Jakarta', 'Surabaya', 'Medan', 'b', '1'];
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $data = collect(self::kolom())->mapWithKeys(function ($k) use ($row) {
                $v = $row[$k] ?? null;
                $v = is_string($v) ? trim($v) : $v;

                return [$k => $v === '' ? null : $v];
            })->all();
            $data['tipe'] = strtolower((string) ($data['tipe'] ?? 'pg')) ?: 'pg';
            $data['kunci_jawaban'] = $data['kunci_jawaban'] !== null ? strtolower((string) $data['kunci_jawaban']) : null;
            $data['bobot'] ??= 1;
            $pg = $data['tipe'] === 'pg';

            $validator = Validator::make($data, [
                'tipe' => ['required', Rule::in(['pg', 'esai'])],
                'pertanyaan' => ['required', 'string'],
                'opsi_a' => [Rule::requiredIf($pg)],
                'opsi_b' => [Rule::requiredIf($pg)],
                'opsi_c' => [Rule::requiredIf($pg)],
                'opsi_d' => [Rule::requiredIf($pg)],
                'kunci_jawaban' => [Rule::requiredIf($pg), 'nullable', Rule::in(Soal::OPSI)],
                'bobot' => ['required', 'integer', 'min:1', 'max:100'],
            ], [], ['kunci_jawaban' => 'kunci jawaban (a/b/c/d)', 'tipe' => 'tipe (pg/esai)']);

            if ($validator->fails()) {
                $this->gagal[] = ['baris' => $index + 2, 'alasan' => $validator->errors()->all()];

                continue;
            }

            if (! $pg) {
                $data = array_merge($data, array_fill_keys(['opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'kunci_jawaban'], null));
            }

            $this->kuis->soal()->create(array_map(fn ($v) => is_numeric($v) && ! is_string($v) ? (string) $v : $v, $data));
            $this->berhasil++;
        }
    }
}
