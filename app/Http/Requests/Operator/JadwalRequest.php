<?php

namespace App\Http\Requests\Operator;

use App\Models\Jadwal;
use App\Models\Pengampu;
use App\Models\TahunAjaran;
use App\Services\JadwalService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class JadwalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pengampu_id' => ['required', Rule::exists('pengampu', 'id')->where('tahun_ajaran_id', TahunAjaran::aktifId())],
            'hari' => ['required', Rule::in(Jadwal::HARI)],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'ruang' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $pesan = app(JadwalService::class)->cekBentrok(
                    Pengampu::findOrFail($this->input('pengampu_id')),
                    $this->input('hari'),
                    $this->input('jam_mulai'),
                    $this->input('jam_selesai'),
                    $this->route('jadwal')?->id,
                );

                if ($pesan) {
                    $validator->errors()->add('jam_mulai', $pesan);
                }
            },
        ];
    }

    public function attributes(): array
    {
        return ['pengampu_id' => 'pengampu', 'jam_mulai' => 'jam mulai', 'jam_selesai' => 'jam selesai'];
    }
}
