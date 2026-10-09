<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class PengaturanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_sekolah' => ['required', 'string', 'max:255'],
            'npsn' => ['nullable', 'string', 'max:20'],
            'alamat_sekolah' => ['nullable', 'string', 'max:500'],
            'telepon_sekolah' => ['nullable', 'string', 'max:30'],
            'email_sekolah' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'min_panjang_password' => ['required', 'integer', 'min:6', 'max:32'],
            'kkm' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_sekolah' => 'nama sekolah', 'npsn' => 'NPSN', 'alamat_sekolah' => 'alamat sekolah',
            'telepon_sekolah' => 'telepon sekolah', 'email_sekolah' => 'email sekolah',
            'min_panjang_password' => 'panjang minimal password', 'kkm' => 'KKM',
        ];
    }
}
