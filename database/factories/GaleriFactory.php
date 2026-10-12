<?php

namespace Database\Factories;

use App\Models\Galeri;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Galeri>
 */
class GaleriFactory extends Factory
{
    public function definition(): array
    {
        return [
            'judul' => rtrim(fake()->sentence(3), '.'),
            'keterangan' => fake()->optional()->sentence(),
            'gambar' => 'galeri/'.fake()->uuid().'.jpg',
            'urutan' => 0,
        ];
    }
}
