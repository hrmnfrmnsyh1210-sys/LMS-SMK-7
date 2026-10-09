<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class MateriRequest extends FormRequest
{
    /** Ekstensi file materi yang diizinkan (dokumen, presentasi, video, gambar, arsip). */
    public const MIMES = 'pdf,doc,docx,ppt,pptx,xls,xlsx,txt,mp4,webm,mkv,mp3,jpg,jpeg,png,zip,rar';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'pertemuan_ke' => ['required', 'integer', 'min:1', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'mimes:'.self::MIMES, 'max:'.config('lms.upload_max_kb')],
            'tautan' => ['nullable', 'url', 'max:500'],
            'hapus_file' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['pertemuan_ke' => 'pertemuan ke', 'file' => 'file materi', 'tautan' => 'tautan (video/link)'];
    }
}
