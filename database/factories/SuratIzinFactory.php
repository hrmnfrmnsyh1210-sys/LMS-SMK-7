<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SuratIzin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SuratIzin>
 */
class SuratIzinFactory extends Factory
{
    public function definition(): array
    {
        return [
            'siswa_id' => Siswa::factory(),
            'kelas_id' => Kelas::factory(),
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->toDateString(),
            'jenis' => fake()->randomElement(['izin', 'sakit']),
            'keterangan' => fake()->sentence(),
            'status' => 'menunggu',
        ];
    }
}
