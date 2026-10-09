<?php

namespace Database\Factories;

use App\Models\Materi;
use App\Models\Pengampu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Materi>
 */
class MateriFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pengampu_id' => Pengampu::factory(),
            'judul' => 'Materi '.ucfirst(fake()->words(3, true)),
            'deskripsi' => fake()->paragraph(),
            'file_path' => null,
            'tautan' => fake()->boolean(40) ? 'https://www.youtube.com/watch?v='.fake()->bothify('???########') : null,
            'pertemuan_ke' => fake()->numberBetween(1, 16),
        ];
    }
}
