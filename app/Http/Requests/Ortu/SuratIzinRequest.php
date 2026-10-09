<?php

namespace App\Http\Requests\Ortu;

use App\Http\Requests\Siswa\SuratIzinRequest as SuratIzinSiswaRequest;
use Illuminate\Validation\Rule;

/** Surat izin yang diajukan orang tua: aturan sama dengan siswa + pilihan anak. */
class SuratIzinRequest extends SuratIzinSiswaRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'siswa_id' => ['required', Rule::in($this->user()->ortu->anak->pluck('id')->all())],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['siswa_id' => 'anak'];
    }
}
