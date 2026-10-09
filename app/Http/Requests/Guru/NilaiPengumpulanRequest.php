<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class NilaiPengumpulanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maks = $this->route('tugas')->nilai_maks;

        return [
            'nilai' => ['required', 'numeric', 'min:0', 'max:'.$maks],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
