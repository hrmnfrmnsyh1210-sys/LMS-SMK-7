<?php

namespace Database\Factories;

use App\Models\Berita;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Berita>
 */
class BeritaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kategori' => fake()->randomElement(array_keys(Berita::KATEGORI)),
            'judul' => rtrim(fake()->unique()->sentence(6), '.'),
            'ringkasan' => fake()->sentence(18),
            'isi' => implode("\n\n", fake()->paragraphs(4)),
            'diterbitkan_pada' => fake()->dateTimeBetween('-3 months', '-1 day'),
        ];
    }

    public function draf(): static
    {
        return $this->state(['diterbitkan_pada' => null]);
    }
}
