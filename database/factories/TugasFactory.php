<?php

namespace Database\Factories;

use App\Models\Pengampu;
use App\Models\Tugas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tugas>
 */
class TugasFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pengampu_id' => Pengampu::factory(),
            'judul' => 'Tugas '.ucfirst(fake()->words(3, true)),
            'deskripsi' => fake()->paragraph(),
            'deadline' => now()->addWeek(),
            'nilai_maks' => 100,
        ];
    }

    public function lewatDeadline(): static
    {
        return $this->state(fn () => ['deadline' => now()->subDay()]);
    }
}
