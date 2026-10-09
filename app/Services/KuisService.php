<?php

namespace App\Services;

use App\Models\JawabanKuis;
use App\Models\JawabanKuisDetail;
use Illuminate\Support\Facades\DB;

/**
 * Penilaian kuis: PG dikoreksi otomatis, esai dikoreksi manual oleh guru.
 * total_skor = Σ skor / Σ bobot seluruh soal × 100, dan baru final (tidak null)
 * jika semua jawaban esai sudah diberi skor.
 */
class KuisService
{
    /** Koreksi otomatis semua jawaban PG lalu hitung total. */
    public function koreksiOtomatis(JawabanKuis $jawaban): JawabanKuis
    {
        $jawaban->loadMissing(['detail.soal', 'kuis.soal']);

        foreach ($jawaban->detail as $detail) {
            if ($detail->soal->isPilihanGanda()) {
                $benar = strtolower(trim((string) $detail->jawaban)) === $detail->soal->kunci_jawaban;
                $detail->update(['skor' => $benar ? $detail->soal->bobot : 0]);
            }
        }

        return $this->hitungTotal($jawaban);
    }

    /**
     * Simpan skor esai dari guru (dibatasi 0..bobot) lalu hitung total.
     *
     * @param  array<int|string, mixed>  $skor  soal_id => skor
     */
    public function koreksiEsai(JawabanKuis $jawaban, array $skor): JawabanKuis
    {
        $jawaban->loadMissing(['detail.soal', 'kuis.soal']);

        DB::transaction(function () use ($jawaban, $skor) {
            foreach ($jawaban->detail as $detail) {
                if ($detail->soal->isPilihanGanda() || ! array_key_exists($detail->soal_id, $skor)) {
                    continue;
                }

                $nilai = $skor[$detail->soal_id];
                $detail->update([
                    'skor' => $nilai === null || $nilai === '' ? null : max(0, min((float) $nilai, $detail->soal->bobot)),
                ]);
            }
        });

        return $this->hitungTotal($jawaban->fresh(['detail.soal', 'kuis.soal']));
    }

    public function hitungTotal(JawabanKuis $jawaban): JawabanKuis
    {
        $jawaban->loadMissing(['detail', 'kuis.soal']);

        $totalBobot = $jawaban->kuis->soal->sum('bobot');
        $detail = $jawaban->detail->keyBy('soal_id');

        // Esai yang sudah dijawab tetapi belum diberi skor → nilai belum final.
        $menunggu = $jawaban->kuis->soal
            ->where('tipe', 'esai')
            ->contains(fn ($soal) => isset($detail[$soal->id]) && $detail[$soal->id]->skor === null
                && filled($detail[$soal->id]->jawaban));

        $total = null;
        if (! $menunggu && $totalBobot > 0) {
            $total = round($detail->sum(fn (JawabanKuisDetail $d) => (float) $d->skor) / $totalBobot * 100, 2);
        }

        $jawaban->update(['total_skor' => $total]);

        return $jawaban;
    }
}
