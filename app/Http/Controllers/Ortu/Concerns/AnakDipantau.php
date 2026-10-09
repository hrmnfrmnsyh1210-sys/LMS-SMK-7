<?php

namespace App\Http\Controllers\Ortu\Concerns;

use App\Models\Siswa;
use Illuminate\Http\Request;

/**
 * Anak yang sedang dipantau orang tua disimpan di session; bila belum dipilih
 * (atau tidak lagi terhubung) dipakai anak pertama.
 */
trait AnakDipantau
{
    protected const SESSION_ANAK = 'ortu.anak_id';

    protected function anakDipantau(Request $request): ?Siswa
    {
        $anak = $request->user()->ortu->anak;

        return $anak->firstWhere('id', $request->session()->get(self::SESSION_ANAK)) ?? $anak->first();
    }
}
