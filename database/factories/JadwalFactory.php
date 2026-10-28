<?php

namespace Database\Factories;

use App\Models\Jadwal;
use App\Models\Pengampu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jadwal>
 */
class JadwalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pengampu_id' => Pengampu::factory(),
            'hari' => fake()->randomElement(Jadwal::HARI),
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'ruang' => null,
        ];
    }

    /** Jadwal pada hari ini (hari Minggu tidak punya jadwal). */
    public function hariIni(): static
    {
        return $this->state(['hari' => Jadwal::hariDari(today())]);
    }
}
