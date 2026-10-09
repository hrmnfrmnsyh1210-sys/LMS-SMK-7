<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MapelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:20', Rule::unique('mapel', 'kode')->ignore($this->route('mapel'))],
            'nama_mapel' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['nama_mapel' => 'nama mata pelajaran'];
    }
}
