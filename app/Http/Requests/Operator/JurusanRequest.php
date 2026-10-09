<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JurusanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:20', Rule::unique('jurusan', 'kode')->ignore($this->route('jurusan'))],
            'nama' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['nama' => 'nama jurusan'];
    }
}
