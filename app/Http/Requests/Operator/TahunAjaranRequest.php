<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TahunAjaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => [
                'required', Rule::in(['ganjil', 'genap']),
                Rule::unique('tahun_ajaran')->where('nama', $this->input('nama'))->ignore($this->route('tahunAjaran')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.regex' => 'Format tahun ajaran harus seperti 2026/2027.',
            'semester.unique' => 'Tahun ajaran dengan semester tersebut sudah ada.',
        ];
    }

    public function attributes(): array
    {
        return ['nama' => 'tahun ajaran'];
    }
}
