<?php

namespace App\Http\Requests\Operator;

use App\Models\Kepsek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KepsekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Kepsek|null $kepsek */
        $kepsek = $this->route('kepsek');

        return [
            'username' => [
                'required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($kepsek?->user_id),
            ],
            'nip' => ['nullable', 'string', 'max:30'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($kepsek?->user_id)],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ];
    }

    public function attributes(): array
    {
        return ['nip' => 'NIP', 'no_hp' => 'nomor HP'];
    }
}
