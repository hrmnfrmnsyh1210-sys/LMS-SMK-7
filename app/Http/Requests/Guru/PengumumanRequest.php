<?php

namespace App\Http\Requests\Guru;

use App\Models\Kelas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Pengumuman guru selalu ditujukan ke kelas yang diampu atau diwalikan. */
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
            'kelas_id' => ['required', Rule::in(self::kelasGuru($this->user()->guru)->pluck('id')->all())],
        ];
    }

    public function attributes(): array
    {
        return ['kelas_id' => 'kelas'];
    }

    /** Kelas tahun aktif yang diajar atau diwalikan guru. */
    public static function kelasGuru($guru)
    {
        return Kelas::tahunAktif()
            ->where(fn ($q) => $q
                ->whereHas('pengampu', fn ($q) => $q->where('guru_id', $guru->id))
                ->orWhere('wali_kelas_id', $guru->id))
            ->orderBy('nama_kelas')
            ->get();
    }
}
