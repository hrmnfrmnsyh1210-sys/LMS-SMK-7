<?php

namespace App\Http\Requests\Guru;

use App\Models\Kuis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KuisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'acak_soal' => $this->boolean('acak_soal'),
            'tampilkan_hasil' => $this->boolean('tampilkan_hasil'),
        ]);
    }

    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'jenis' => ['required', Rule::in(array_keys(Kuis::JENIS))],
            'waktu_mulai' => ['required', 'date'],
            'waktu_selesai' => ['required', 'date', 'after:waktu_mulai'],
            'durasi_menit' => ['required', 'integer', 'min:1', 'max:600'],
            'acak_soal' => ['boolean'],
            'tampilkan_hasil' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'waktu_mulai' => 'waktu mulai', 'waktu_selesai' => 'waktu selesai', 'durasi_menit' => 'durasi',
        ];
    }
}
