<?php

namespace Database\Factories;

use App\Models\JawabanKuis;
use App\Models\Kuis;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JawabanKuis>
 */
class JawabanKuisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kuis_id' => Kuis::factory(),
            'siswa_id' => Siswa::factory(),
            'waktu_mulai' => now()->subMinutes(30),
            'waktu_submit' => now(),
            'total_skor' => null,
            'jumlah_pindah_tab' => 0,
        ];
    }
}
