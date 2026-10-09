<?php

namespace App\Imports;

use App\Models\User;
use App\Services\AkunService;
use Illuminate\Validation\Rule;

class GuruImport extends AkunImport
{
    public function __construct(private AkunService $akun) {}

    public static function kolom(): array
    {
        return ['nip', 'username', 'nama', 'jenis_kelamin', 'no_hp', 'alamat', 'email'];
    }

    public static function contoh(): array
    {
        return ['198501012010011001', '', 'Budi Santoso, S.Kom.', 'L', '081234567890', 'Jl. Merdeka No. 1', 'budi@example.com'];
    }

    protected function rules(array $row): array
    {
        $username = $row['username'] ?: $row['nip'];

        return [
            'nip' => ['nullable', 'string', 'max:30', 'alpha_dash', 'unique:guru,nip'],
            'username' => [
                Rule::requiredIf(blank($row['nip'])), 'nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/',
                function ($attr, $value, $fail) use ($username) {
                    if ($username && User::where('username', $username)->exists()) {
                        $fail("Username {$username} sudah dipakai.");
                    }
                },
            ],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
        ];
    }

    protected function attributes(): array
    {
        return ['nip' => 'NIP', 'no_hp' => 'nomor HP', 'jenis_kelamin' => 'jenis kelamin (L/P)'];
    }

    protected function simpan(array $row): void
    {
        $this->akun->buatGuru($row);
    }

    protected function ringkas(array $row): string
    {
        return trim(($row['nip'] ?? $row['username'] ?? '-').' — '.($row['nama'] ?? '-'));
    }
}
