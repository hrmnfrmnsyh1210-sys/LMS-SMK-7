<?php

namespace Database\Factories;

use App\Models\Kuis;
use App\Models\Soal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Soal>
 */
class SoalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kuis_id' => Kuis::factory(),
            'tipe' => 'pg',
            'pertanyaan' => rtrim(fake()->sentence(8), '.').'?',
            'opsi_a' => fake()->words(2, true),
            'opsi_b' => fake()->words(2, true),
            'opsi_c' => fake()->words(2, true),
            'opsi_d' => fake()->words(2, true),
            'kunci_jawaban' => fake()->randomElement(Soal::OPSI),
            'bobot' => 1,
        ];
    }

    public function esai(): static
    {
        return $this->state(fn () => [
            'tipe' => 'esai',
            'opsi_a' => null, 'opsi_b' => null, 'opsi_c' => null, 'opsi_d' => null,
            'kunci_jawaban' => null,
        ]);
    }
}
