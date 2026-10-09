<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'email' => null,
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'siswa',
            'status' => 'aktif',
            'wajib_ganti_password' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function role(string $role): static
    {
        return $this->state(fn () => ['role' => $role]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['status' => 'nonaktif']);
    }

    public function wajibGantiPassword(): static
    {
        return $this->state(fn () => ['wajib_ganti_password' => true]);
    }

    /** User operator lengkap dengan profilnya. */
    public function operator(): static
    {
        return $this->role('operator')->afterCreating(
            fn (User $user) => $user->operator()->create(['nama' => fake()->name()])
        );
    }
}
