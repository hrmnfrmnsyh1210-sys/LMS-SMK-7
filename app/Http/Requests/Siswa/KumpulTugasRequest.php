<?php

namespace App\Http\Requests\Siswa;

use App\Http\Requests\Guru\MateriRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Pengumpulan tugas: file dan/atau jawaban teks (minimal salah satu). */
class KumpulTugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $fileLama = $this->route('tugas')->pengumpulan()->where('siswa_id', $this->user()->siswa->id)->value('file_path');

        return [
            'file' => ['nullable', 'file', 'mimes:'.MateriRequest::MIMES, 'max:'.config('lms.upload_max_kb')],
            'jawaban_teks' => [
                Rule::requiredIf(fn () => ! $this->hasFile('file') && ! $fileLama),
                'nullable', 'string', 'max:20000',
            ],
        ];
    }

    public function attributes(): array
    {
        return ['file' => 'file jawaban', 'jawaban_teks' => 'jawaban'];
    }

    public function messages(): array
    {
        return ['jawaban_teks.required' => 'Unggah file atau tulis jawaban terlebih dahulu.'];
    }
}
