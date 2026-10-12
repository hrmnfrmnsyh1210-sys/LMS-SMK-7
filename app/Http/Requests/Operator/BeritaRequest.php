<?php

namespace App\Http\Requests\Operator;

use App\Models\Berita;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BeritaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kategori' => ['required', Rule::in(array_keys(Berita::KATEGORI))],
            'judul' => ['required', 'string', 'max:200'],
            'ringkasan' => ['nullable', 'string', 'max:300'],
            'isi' => ['required', 'string', 'max:50000'],
            'gambar' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'hapus_gambar' => ['nullable', 'boolean'],
            'terbitkan' => ['nullable', 'boolean'],
            'diterbitkan_pada' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return ['isi' => 'isi berita', 'diterbitkan_pada' => 'tanggal terbit'];
    }
}
