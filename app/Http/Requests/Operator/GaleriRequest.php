<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class GaleriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'gambar' => [$this->route('galeri') ? 'nullable' : 'required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function attributes(): array
    {
        return ['judul' => 'judul foto', 'gambar' => 'foto'];
    }
}
