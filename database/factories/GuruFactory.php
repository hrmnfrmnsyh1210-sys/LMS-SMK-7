<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guru>
 */
class GuruFactory extends Factory
{
    public function definition(): array
    {
        $jk = fake()->randomElement(['L', 'P']);

        return [
            'nip' => fake()->unique()->numerify('19##########0#1###'),
            'user_id' => fn (array $attr) => User::factory()->role('guru')->create(['username' => $attr['nip']])->id,
            'nama' => fake()->name($jk === 'L' ? 'male' : 'female'),
            'jenis_kelamin' => $jk,
            'no_hp' => fake()->numerify('08##########'),
            'alamat' => fake()->address(),
        ];
    }
}
