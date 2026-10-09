<?php

namespace App\Http\Requests\Operator;

use App\Models\OrangTua;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrangTuaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var OrangTua|null $ortu */
        $ortu = $this->route('orangTua');

        return [
            'username' => [
                'required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($ortu?->user_id),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'hubungan' => ['required', Rule::in(array_keys(OrangTua::HUBUNGAN))],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ortu?->user_id)],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            'siswa_ids' => ['required', 'array', 'min:1'],
            'siswa_ids.*' => ['integer', 'distinct', Rule::exists('siswa', 'id')],
        ];
    }

    public function attributes(): array
    {
        return ['no_hp' => 'nomor HP', 'siswa_ids' => 'anak', 'siswa_ids.*' => 'anak'];
    }

    public function messages(): array
    {
        return ['siswa_ids.required' => 'Pilih minimal satu anak yang dipantau.'];
    }
}
