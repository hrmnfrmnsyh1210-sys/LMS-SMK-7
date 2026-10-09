<?php

namespace Database\Factories;

use App\Models\Kepsek;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kepsek>
 */
class KepsekFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->role('kepsek'),
            'nip' => fake()->numerify('19##########0#1###'),
            'nama' => fake()->name(),
            'no_hp' => fake()->numerify('08##########'),
            'jenis_kelamin' => fake()->randomElement(['L', 'P']),
        ];
    }
}
