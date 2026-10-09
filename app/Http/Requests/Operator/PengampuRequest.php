<?php

namespace App\Http\Requests\Operator;

use App\Models\Pengampu;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PengampuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Pengampu|null $pengampu */
        $pengampu = $this->route('pengampu');
        $tahunId = $pengampu?->tahun_ajaran_id ?? TahunAjaran::aktifId();

        return [
            'guru_id' => ['required', 'exists:guru,id'],
            'mapel_id' => [
                'required', 'exists:mapel,id',
                Rule::unique('pengampu')
                    ->where('kelas_id', $this->input('kelas_id'))
                    ->where('tahun_ajaran_id', $tahunId)
                    ->ignore($pengampu?->id),
            ],
            'kelas_id' => ['required', Rule::exists('kelas', 'id')->where('tahun_ajaran_id', $tahunId)],
        ];
    }

    public function messages(): array
    {
        return ['mapel_id.unique' => 'Mata pelajaran ini sudah memiliki pengampu di kelas tersebut.'];
    }

    public function attributes(): array
    {
        return ['guru_id' => 'guru', 'mapel_id' => 'mata pelajaran', 'kelas_id' => 'kelas'];
    }
}
