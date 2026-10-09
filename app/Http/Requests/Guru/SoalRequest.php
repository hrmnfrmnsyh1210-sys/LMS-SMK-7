<?php

namespace App\Http\Requests\Guru;

use App\Models\Soal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pg = $this->input('tipe') === 'pg';

        return [
            'tipe' => ['required', Rule::in(['pg', 'esai'])],
            'pertanyaan' => ['required', 'string', 'max:10000'],
            'opsi_a' => [Rule::requiredIf($pg), 'nullable', 'string', 'max:2000'],
            'opsi_b' => [Rule::requiredIf($pg), 'nullable', 'string', 'max:2000'],
            'opsi_c' => [Rule::requiredIf($pg), 'nullable', 'string', 'max:2000'],
            'opsi_d' => [Rule::requiredIf($pg), 'nullable', 'string', 'max:2000'],
            'kunci_jawaban' => [Rule::requiredIf($pg), 'nullable', Rule::in(Soal::OPSI)],
            'bobot' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    /** Soal esai tidak menyimpan opsi & kunci. */
    public function dataSoal(): array
    {
        $data = $this->validated();
        if ($data['tipe'] === 'esai') {
            $data = array_merge($data, array_fill_keys(['opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'kunci_jawaban'], null));
        }

        return $data;
    }

    public function attributes(): array
    {
        return [
            'opsi_a' => 'opsi A', 'opsi_b' => 'opsi B', 'opsi_c' => 'opsi C', 'opsi_d' => 'opsi D',
            'kunci_jawaban' => 'kunci jawaban',
        ];
    }
}
