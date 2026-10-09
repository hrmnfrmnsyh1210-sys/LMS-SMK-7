<?php

namespace Database\Factories;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    public function definition(): array
    {
        $tingkat = fake()->randomElement(Kelas::TINGKAT);

        return [
            'tingkat' => $tingkat,
            'jurusan_id' => Jurusan::factory(),
            'nama_kelas' => fn (array $attr) => $attr['tingkat'].' '.Jurusan::find($attr['jurusan_id'])->kode.' '.fake()->unique()->numberBetween(1, 999),
            'wali_kelas_id' => null,
            'tahun_ajaran_id' => fn () => TahunAjaran::aktifId() ?? TahunAjaran::factory()->aktif()->create()->id,
        ];
    }
}
