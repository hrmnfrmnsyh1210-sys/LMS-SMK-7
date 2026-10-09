<?php

namespace Database\Factories;

use App\Models\Kuis;
use App\Models\Pengampu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kuis>
 */
class KuisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pengampu_id' => Pengampu::factory(),
            'judul' => 'Kuis '.ucfirst(fake()->words(2, true)),
            'jenis' => 'kuis',
            'waktu_mulai' => now()->subHour(),
            'waktu_selesai' => now()->addDay(),
            'durasi_menit' => 60,
            'acak_soal' => false,
            'tampilkan_hasil' => true,
        ];
    }

    public function selesai(): static
    {
        return $this->state(fn () => ['waktu_mulai' => now()->subDays(2), 'waktu_selesai' => now()->subDay()]);
    }
}
