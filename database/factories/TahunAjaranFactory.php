<?php

namespace Database\Factories;

use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TahunAjaran>
 */
class TahunAjaranFactory extends Factory
{
    public function definition(): array
    {
        $awal = fake()->unique()->numberBetween(2020, 2040);

        return [
            'nama' => $awal.'/'.($awal + 1),
            'semester' => 'ganjil',
            'status_aktif' => false,
        ];
    }

    public function aktif(): static
    {
        return $this->state(fn () => ['status_aktif' => true]);
    }
}
