<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProsesSuratIzinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('proses', $this->route('suratIzin'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['disetujui', 'ditolak'])],
            'catatan' => [Rule::requiredIf($this->input('status') === 'ditolak'), 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return ['catatan.required' => 'Tuliskan alasan penolakan.'];
    }
}
