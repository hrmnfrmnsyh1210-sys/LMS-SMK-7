<?php

namespace Database\Factories;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Siswa>
 */
class SiswaFactory extends Factory
{
    public function definition(): array
    {
        $jk = fake()->randomElement(['L', 'P']);

        return [
            'nis' => fake()->unique()->numerify('26###'),
            'user_id' => fn (array $attr) => User::factory()->role('siswa')->create(['username' => $attr['nis']])->id,
            'nisn' => fake()->unique()->numerify('00########'),
            'nama' => fake()->name($jk === 'L' ? 'male' : 'female'),
            'jenis_kelamin' => $jk,
            'tgl_lahir' => fake()->dateTimeBetween('-18 years', '-15 years')->format('Y-m-d'),
            'alamat' => fake()->address(),
            'nama_ortu' => fake()->name(),
            'status' => 'aktif',
        ];
    }
}
