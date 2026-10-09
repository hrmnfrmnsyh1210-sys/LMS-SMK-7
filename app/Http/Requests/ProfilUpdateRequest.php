<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfilUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        $rules = [
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ];

        // Nama siswa mengikuti data induk sekolah, hanya bisa diubah operator.
        if ($user->role !== 'siswa') {
            $rules['nama'] = ['required', 'string', 'max:255'];
        }

        return $rules + match ($user->role) {
            'operator' => ['no_hp' => ['nullable', 'string', 'max:20']],
            'kepsek' => [
                'no_hp' => ['nullable', 'string', 'max:20'],
                'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            ],
            'guru' => [
                'no_hp' => ['nullable', 'string', 'max:20'],
                'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
                'alamat' => ['nullable', 'string', 'max:500'],
            ],
            'siswa' => [
                'alamat' => ['nullable', 'string', 'max:500'],
                'nama_ortu' => ['nullable', 'string', 'max:255'],
            ],
            'ortu' => [
                'no_hp' => ['nullable', 'string', 'max:20'],
                'alamat' => ['nullable', 'string', 'max:500'],
            ],
            default => [],
        };
    }
}
