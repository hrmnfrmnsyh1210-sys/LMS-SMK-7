<?php

namespace App\Http\Requests\Operator;

use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('tahun_ajaran_id')) {
            $this->merge(['tahun_ajaran_id' => TahunAjaran::aktifId()]);
        }
    }

    public function rules(): array
    {
        /** @var Kelas|null $kelas */
        $kelas = $this->route('kelas');

        return [
            'nama_kelas' => [
                'required', 'string', 'max:50',
                Rule::unique('kelas')->where('tahun_ajaran_id', $this->input('tahun_ajaran_id'))->ignore($kelas?->id),
            ],
            'tingkat' => ['required', Rule::in(Kelas::TINGKAT)],
            'jurusan_id' => ['required', 'exists:jurusan,id'],
            'wali_kelas_id' => ['nullable', 'exists:guru,id'],
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
        ];
    }

    public function messages(): array
    {
        return ['nama_kelas.unique' => 'Nama kelas sudah dipakai pada tahun ajaran tersebut.'];
    }

    public function attributes(): array
    {
        return [
            'nama_kelas' => 'nama kelas', 'jurusan_id' => 'jurusan',
            'wali_kelas_id' => 'wali kelas', 'tahun_ajaran_id' => 'tahun ajaran',
        ];
    }
}
