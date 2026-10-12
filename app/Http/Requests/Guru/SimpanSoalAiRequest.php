<?php

namespace App\Http\Requests\Guru;

use App\Models\Soal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Draf soal AI yang dipilih guru (fieldset yang tidak dipilih dinonaktifkan sehingga tidak terkirim). */
class SimpanSoalAiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'soal' => ['required', 'array', 'min:1', 'max:20'],
            'soal.*.tipe' => ['required', Rule::in(['pg', 'esai'])],
            'soal.*.pertanyaan' => ['required', 'string', 'max:10000'],
            'soal.*.opsi_a' => ['required_if:soal.*.tipe,pg', 'nullable', 'string', 'max:2000'],
            'soal.*.opsi_b' => ['required_if:soal.*.tipe,pg', 'nullable', 'string', 'max:2000'],
            'soal.*.opsi_c' => ['required_if:soal.*.tipe,pg', 'nullable', 'string', 'max:2000'],
            'soal.*.opsi_d' => ['required_if:soal.*.tipe,pg', 'nullable', 'string', 'max:2000'],
            'soal.*.kunci_jawaban' => ['required_if:soal.*.tipe,pg', 'nullable', Rule::in(Soal::OPSI)],
            'soal.*.bobot' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return ['soal.required' => 'Pilih minimal satu soal untuk disimpan.'];
    }

    public function attributes(): array
    {
        return [
            'soal.*.pertanyaan' => 'pertanyaan',
            'soal.*.opsi_a' => 'opsi A', 'soal.*.opsi_b' => 'opsi B', 'soal.*.opsi_c' => 'opsi C', 'soal.*.opsi_d' => 'opsi D',
            'soal.*.kunci_jawaban' => 'kunci jawaban',
            'soal.*.bobot' => 'bobot',
        ];
    }

    /**
     * Soal esai tidak menyimpan opsi & kunci.
     *
     * @return list<array<string, mixed>>
     */
    public function dataSoal(): array
    {
        $kolom = ['tipe', 'pertanyaan', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'kunci_jawaban', 'bobot'];

        return collect($this->validated('soal'))
            ->map(function (array $s) use ($kolom) {
                $s = array_merge(array_fill_keys($kolom, null), array_intersect_key($s, array_flip($kolom)));

                return $s['tipe'] === 'esai'
                    ? array_merge($s, array_fill_keys(['opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'kunci_jawaban'], null))
                    : $s;
            })
            ->values()
            ->all();
    }
}
