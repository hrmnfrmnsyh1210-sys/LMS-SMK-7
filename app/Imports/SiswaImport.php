<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\TahunAjaran;
use App\Services\AkunService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SiswaImport extends AkunImport
{
    /** @var array<string, int> nama_kelas (lowercase) => id, pada tahun ajaran aktif */
    private array $kelas;

    public function __construct(private AkunService $akun)
    {
        $this->kelas = Kelas::tahunAktif()->get()
            ->mapWithKeys(fn ($k) => [mb_strtolower($k->nama_kelas) => $k->id])
            ->all();
    }

    public static function kolom(): array
    {
        return ['nis', 'nisn', 'nama', 'jenis_kelamin', 'tgl_lahir', 'alamat', 'nama_ortu', 'kelas'];
    }

    public static function contoh(): array
    {
        return ['26001', '0091234567', 'Siti Aminah', 'P', '2010-05-17', 'Jl. Melati No. 5', 'Ahmad Fauzi', 'X RPL 1'];
    }

    protected function rules(array $row): array
    {
        return [
            'nis' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:siswa,nis', 'unique:users,username'],
            'nisn' => ['required', 'string', 'max:20', 'unique:siswa,nisn'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tgl_lahir' => ['nullable', 'date', 'before:today'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'nama_ortu' => ['nullable', 'string', 'max:255'],
            'kelas' => [
                'nullable', 'string',
                function ($attr, $value, $fail) {
                    if (! isset($this->kelas[mb_strtolower($value)])) {
                        $fail("Kelas \"{$value}\" tidak ditemukan pada tahun ajaran aktif.");
                    }
                },
            ],
        ];
    }

    protected function attributes(): array
    {
        return [
            'nis' => 'NIS', 'nisn' => 'NISN', 'tgl_lahir' => 'tanggal lahir',
            'nama_ortu' => 'nama orang tua', 'jenis_kelamin' => 'jenis kelamin (L/P)',
        ];
    }

    protected function simpan(array $row): void
    {
        DB::transaction(function () use ($row) {
            $siswa = $this->akun->buatSiswa($row);

            if (! empty($row['kelas'])) {
                KelasSiswa::create([
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $this->kelas[mb_strtolower($row['kelas'])],
                    'tahun_ajaran_id' => TahunAjaran::aktifId(),
                ]);
            }
        });
    }

    protected function ringkas(array $row): string
    {
        return trim(($row['nis'] ?? '-').' — '.($row['nama'] ?? '-'));
    }
}
