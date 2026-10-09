<?php

namespace App\Http\Requests\Siswa;

use Illuminate\Foundation\Http\FormRequest;

class SuratIzinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis' => ['required', 'in:izin,sakit'],
            'tanggal_mulai' => ['required', 'date', 'after_or_equal:'.now()->subDays(7)->toDateString()],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai', 'before_or_equal:'.now()->addDays(30)->toDateString()],
            'keterangan' => ['required', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return ['tanggal_mulai' => 'tanggal mulai', 'tanggal_selesai' => 'tanggal selesai', 'file' => 'bukti'];
    }

    public function messages(): array
    {
        return [
            'tanggal_mulai.after_or_equal' => 'Surat izin hanya dapat diajukan paling lama 7 hari setelah tanggal izin.',
            'tanggal_selesai.before_or_equal' => 'Rentang izin maksimal sampai 30 hari ke depan.',
        ];
    }
}
