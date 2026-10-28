<?php

namespace Database\Factories;

use App\Models\AbsensiGuru;
use App\Models\Jadwal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AbsensiGuru>
 */
class AbsensiGuruFactory extends Factory
{
    public function definition(): array
    {
        return [
            'jadwal_id' => Jadwal::factory(),
            'guru_id' => fn (array $attr) => Jadwal::find($attr['jadwal_id'])->pengampu->guru_id,
            'tanggal' => today(),
            'jam_masuk' => '07:00:00',
            'jam_keluar' => null,
            'status' => 'hadir',
        ];
    }

    public function terlambat(): static
    {
        return $this->state(['jam_masuk' => '07:20:00', 'status' => 'terlambat']);
    }
}
