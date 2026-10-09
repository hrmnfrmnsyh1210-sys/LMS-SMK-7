<?php

namespace App\Http\Requests\Kepsek;

use Illuminate\Foundation\Http\FormRequest;

/** Pengumuman sekolah dari kepala sekolah: untuk semua, guru, siswa, atau orang tua. */
class PengumumanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'isi' => ['required', 'string', 'max:10000'],
            'target' => ['required', 'in:semua,guru,siswa,ortu'],
        ];
    }

    public function attributes(): array
    {
        return ['target' => 'tujuan'];
    }
}
