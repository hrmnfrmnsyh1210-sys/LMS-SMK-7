<?php

namespace Database\Factories;

use App\Models\OrangTua;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrangTua>
 */
class OrangTuaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->role('ortu'),
            'nama' => fake()->name(),
            'hubungan' => fake()->randomElement(array_keys(OrangTua::HUBUNGAN)),
            'no_hp' => fake()->numerify('08##########'),
            'alamat' => fake()->address(),
        ];
    }

    /** Orang tua yang langsung terhubung ke siswa tertentu. */
    public function untuk(Siswa ...$siswa): static
    {
        return $this->afterCreating(fn (OrangTua $ortu) => $ortu->anak()->attach(collect($siswa)->pluck('id')));
    }
}
