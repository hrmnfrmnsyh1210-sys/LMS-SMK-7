<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Produksi: hanya akun operator awal & pengaturan.
     * Lokal/testing: data dummy lengkap.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->call(ProduksiSeeder::class);

            return;
        }

        $this->call([DummySeeder::class, DummyPembelajaranSeeder::class]);
    }
}
