<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BobotNilaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bobot_tugas' => ['required', 'integer', 'min:0', 'max:100'],
            'bobot_kuis' => ['required', 'integer', 'min:0', 'max:100'],
            'bobot_uts' => ['required', 'integer', 'min:0', 'max:100'],
            'bobot_uas' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $total = collect($this->only(['bobot_tugas', 'bobot_kuis', 'bobot_uts', 'bobot_uas']))->sum();
                if (! $validator->errors()->any() && (int) $total !== 100) {
                    $validator->errors()->add('bobot_tugas', "Total bobot harus 100%, saat ini {$total}%.");
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'bobot_tugas' => 'bobot tugas', 'bobot_kuis' => 'bobot kuis', 'bobot_uts' => 'bobot UTS', 'bobot_uas' => 'bobot UAS',
        ];
    }
}
