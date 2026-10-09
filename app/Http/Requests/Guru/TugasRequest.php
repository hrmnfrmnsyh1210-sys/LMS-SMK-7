<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class TugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string', 'max:10000'],
            'deadline' => ['required', 'date'],
            'nilai_maks' => ['required', 'integer', 'min:1', 'max:1000'],
            'file' => ['nullable', 'file', 'mimes:'.MateriRequest::MIMES, 'max:'.config('lms.upload_max_kb')],
            'hapus_file' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['nilai_maks' => 'nilai maksimal', 'file' => 'lampiran'];
    }
}
