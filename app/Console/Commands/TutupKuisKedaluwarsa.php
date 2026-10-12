<?php

namespace App\Console\Commands;

use App\Models\JawabanKuis;
use App\Services\PengerjaanKuisService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Kirim otomatis pengerjaan kuis yang waktunya sudah habis tetapi belum dikirim
 * (mis. siswa menutup browser), agar skornya langsung terhitung untuk guru.
 */
#[Signature('lms:tutup-kuis')]
#[Description('Kirim otomatis pengerjaan kuis yang waktunya sudah habis')]
class TutupKuisKedaluwarsa extends Command
{
    public function handle(PengerjaanKuisService $service): int
    {
        $jumlah = 0;

        JawabanKuis::with('kuis')
            ->whereNull('waktu_submit')
            ->lazyById()
            ->each(function (JawabanKuis $jawaban) use ($service, &$jumlah) {
                // Beri jeda toleransi agar tidak mendahului kiriman terakhir dari browser siswa.
                if (now()->gt($service->batasWaktu($jawaban)->addSeconds(PengerjaanKuisService::TOLERANSI_DETIK))) {
                    $service->submit($jawaban);
                    $jumlah++;
                }
            });

        $this->info("{$jumlah} pengerjaan kuis dikirim otomatis.");

        return self::SUCCESS;
    }
}
