<?php

namespace App\Services;

use App\Models\JawabanKuis;
use App\Models\Kuis;
use App\Models\Siswa;
use App\Models\Soal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Alur siswa mengerjakan kuis: mulai → autosave per soal → submit.
 * Batas waktu = waktu mulai siswa + durasi, tetapi tidak melewati waktu selesai kuis.
 */
class PengerjaanKuisService
{
    /** Toleransi keterlambatan jaringan saat menyimpan/mengirim jawaban (detik). */
    public const TOLERANSI_DETIK = 30;

    public function __construct(private KuisService $kuisService) {}

    /** Mulai (atau lanjutkan) pengerjaan; urutan soal diacak per siswa bila diatur. */
    public function mulai(Kuis $kuis, Siswa $siswa): JawabanKuis
    {
        return DB::transaction(function () use ($kuis, $siswa) {
            $jawaban = JawabanKuis::where('kuis_id', $kuis->id)->where('siswa_id', $siswa->id)->lockForUpdate()->first();

            if ($jawaban) {
                return $jawaban;
            }

            $urutan = $kuis->soal()->orderBy('id')->pluck('id');

            return JawabanKuis::create([
                'kuis_id' => $kuis->id,
                'siswa_id' => $siswa->id,
                'waktu_mulai' => now(),
                'urutan_soal' => ($kuis->acak_soal ? $urutan->shuffle() : $urutan)->values()->all(),
            ]);
        });
    }

    public function batasWaktu(JawabanKuis $jawaban): Carbon
    {
        $jawaban->loadMissing('kuis');

        return $jawaban->waktu_mulai->copy()->addMinutes($jawaban->kuis->durasi_menit)->min($jawaban->kuis->waktu_selesai);
    }

    public function sisaDetik(JawabanKuis $jawaban): int
    {
        return max(0, (int) now()->diffInSeconds($this->batasWaktu($jawaban), false));
    }

    /** Masih boleh menyimpan jawaban (belum submit dan belum lewat batas + toleransi). */
    public function masihBisaMenjawab(JawabanKuis $jawaban): bool
    {
        return ! $jawaban->sudahSubmit()
            && now()->lte($this->batasWaktu($jawaban)->addSeconds(self::TOLERANSI_DETIK));
    }

    /**
     * Soal sesuai urutan milik siswa. Soal yang ditambahkan setelah siswa mulai ditaruh di akhir.
     *
     * @return Collection<int, Soal>
     */
    public function soalTerurut(JawabanKuis $jawaban): Collection
    {
        $jawaban->loadMissing('kuis.soal');
        $posisi = array_flip($jawaban->urutan_soal ?? []);

        return $jawaban->kuis->soal
            ->sortBy(fn (Soal $s) => [$posisi[$s->id] ?? PHP_INT_MAX, $s->id])
            ->values();
    }

    /** Simpan satu jawaban (autosave). Jawaban PG yang tidak valid diabaikan. */
    public function simpanJawaban(JawabanKuis $jawaban, Soal $soal, ?string $isi): void
    {
        $isi = $isi === null ? null : trim($isi);

        if ($soal->isPilihanGanda()) {
            $isi = in_array(strtolower((string) $isi), Soal::OPSI, true) ? strtolower($isi) : null;
        }

        $jawaban->detail()->updateOrCreate(['soal_id' => $soal->id], ['jawaban' => $isi === '' ? null : $isi]);
    }

    public function catatPindahTab(JawabanKuis $jawaban): void
    {
        if (! $jawaban->sudahSubmit()) {
            $jawaban->increment('jumlah_pindah_tab');
        }
    }

    /**
     * Simpan jawaban terakhir (bila masih dalam waktu), tandai selesai, lalu koreksi otomatis.
     *
     * @param  array<int|string, mixed>  $jawabanAkhir  soal_id => jawaban
     */
    public function submit(JawabanKuis $jawaban, array $jawabanAkhir = []): JawabanKuis
    {
        if ($jawaban->sudahSubmit()) {
            return $jawaban;
        }

        DB::transaction(function () use ($jawaban, $jawabanAkhir) {
            if ($this->masihBisaMenjawab($jawaban)) {
                $soal = $jawaban->kuis->soal->keyBy('id');
                foreach ($jawabanAkhir as $soalId => $isi) {
                    if ($soal->has($soalId) && (is_string($isi) || $isi === null)) {
                        $this->simpanJawaban($jawaban, $soal[$soalId], $isi);
                    }
                }
            }

            $jawaban->update(['waktu_submit' => now()->min($this->batasWaktu($jawaban)->addSeconds(self::TOLERANSI_DETIK))]);
        });

        return $this->kuisService->koreksiOtomatis($jawaban->fresh(['detail.soal', 'kuis.soal']));
    }

    /** Status kuis bagi siswa; kunci dari Kuis::STATUS_SISWA. */
    public function statusSiswa(Kuis $kuis, ?JawabanKuis $jawaban): string
    {
        return match (true) {
            $jawaban?->sudahSubmit() => 'dikumpulkan',
            $jawaban !== null => $this->sisaDetik($jawaban) > 0 ? 'dikerjakan' : 'dikumpulkan',
            $kuis->status_waktu === 'berlangsung' => 'tersedia',
            $kuis->status_waktu === 'akan_datang' => 'akan_datang',
            default => 'terlewat',
        };
    }

    /** Submit otomatis bila waktu siswa sudah habis tetapi belum mengirim. */
    public function submitBilaWaktuHabis(JawabanKuis $jawaban): JawabanKuis
    {
        if (! $jawaban->sudahSubmit() && $this->sisaDetik($jawaban) === 0) {
            return $this->submit($jawaban);
        }

        return $jawaban;
    }
}
