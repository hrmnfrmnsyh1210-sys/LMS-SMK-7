<?php

namespace App\Http\Requests\Guru;

use App\Services\PresensiService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PresensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pertemuan_ke' => ['required', 'integer', 'min:1', 'max:100'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', 'array'],
            'status.*' => ['required', Rule::in(array_keys(PresensiService::STATUS))],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'materi' => ['nullable', 'string', 'max:1000'],
            'tugas' => ['nullable', 'string', 'max:1000'],
            'dokumentasi' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'hapus_dokumentasi' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['pertemuan_ke' => 'pertemuan ke', 'status.*' => 'status kehadiran'];
    }
}
