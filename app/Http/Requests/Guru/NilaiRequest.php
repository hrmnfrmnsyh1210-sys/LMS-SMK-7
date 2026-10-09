<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class NilaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nilai' => ['array'],
            'nilai.*.nilai_uts' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai.*.nilai_uas' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return ['nilai.*.nilai_uts' => 'nilai UTS', 'nilai.*.nilai_uas' => 'nilai UAS'];
    }
}
