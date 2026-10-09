<?php

namespace App\Http\Requests\Operator;

use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Siswa|null $siswa */
        $siswa = $this->route('siswa');

        return [
            'nis' => [
                'required', 'string', 'max:20', 'alpha_dash',
                Rule::unique('siswa', 'nis')->ignore($siswa?->id),
                Rule::unique('users', 'username')->ignore($siswa?->user_id),
            ],
            'nisn' => ['required', 'string', 'max:20', Rule::unique('siswa', 'nisn')->ignore($siswa?->id)],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tgl_lahir' => ['nullable', 'date', 'before:today'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'nama_ortu' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($siswa?->user_id)],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            'kelas_id' => ['nullable', Rule::exists('kelas', 'id')->where('tahun_ajaran_id', TahunAjaran::aktifId())],
        ];
    }

    public function attributes(): array
    {
        return [
            'nis' => 'NIS', 'nisn' => 'NISN', 'tgl_lahir' => 'tanggal lahir',
            'nama_ortu' => 'nama orang tua', 'kelas_id' => 'kelas',
        ];
    }
}
