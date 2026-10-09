<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Pengampu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengampu>
 */
class PengampuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'guru_id' => Guru::factory(),
            'mapel_id' => Mapel::factory(),
            'kelas_id' => Kelas::factory(),
            'tahun_ajaran_id' => fn (array $attr) => Kelas::find($attr['kelas_id'])->tahun_ajaran_id,
        ];
    }
}
