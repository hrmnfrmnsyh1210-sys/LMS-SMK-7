<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

/** Topik baru (judul + isi) atau balasan (isi saja). */
class ForumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = ['isi' => ['required', 'string', 'max:5000']];

        if (! $this->route('topik')) {
            $rules['judul'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }
}
