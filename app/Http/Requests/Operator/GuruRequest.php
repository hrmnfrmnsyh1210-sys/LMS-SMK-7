<?php

namespace App\Http\Requests\Operator;

use App\Models\Guru;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuruRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Guru|null $guru */
        $guru = $this->route('guru');
        $userId = $guru?->user_id;

        return [
            'nip' => array_filter([
                'nullable', 'string', 'max:30', 'alpha_dash', Rule::unique('guru', 'nip')->ignore($guru?->id),
                // Saat username dikosongkan, NIP dipakai sebagai username sehingga harus unik juga.
                ! $guru && blank($this->input('username')) ? Rule::unique('users', 'username') : null,
            ]),
            'username' => [
                Rule::requiredIf(fn () => blank($this->input('nip'))),
                'nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($userId),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ];
    }

    public function messages(): array
    {
        return ['username.required' => 'Username wajib diisi jika NIP kosong.'];
    }

    public function attributes(): array
    {
        return ['nip' => 'NIP', 'no_hp' => 'nomor HP'];
    }
}
