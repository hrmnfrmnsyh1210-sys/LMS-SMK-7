<?php

namespace Database\Factories;

use App\Models\PengumpulanTugas;
use App\Models\Siswa;
use App\Models\Tugas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengumpulanTugas>
 */
class PengumpulanTugasFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tugas_id' => Tugas::factory(),
            'siswa_id' => Siswa::factory(),
            'jawaban_teks' => fake()->paragraph(),
            'waktu_kumpul' => now(),
            'nilai' => null,
            'feedback' => null,
        ];
    }
}
