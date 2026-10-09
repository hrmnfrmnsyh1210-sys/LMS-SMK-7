<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProduksiSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Pengaturan::DEFAULT as $kunci => $nilai) {
            Pengaturan::firstOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
        }

        if (! User::where('username', 'operator')->exists()) {
            $user = User::create([
                'username' => 'operator',
                'password' => 'password',
                'role' => 'operator',
                'status' => 'aktif',
                // Wajib ganti password saat login pertama.
                'wajib_ganti_password' => true,
            ]);
            $user->operator()->create(['nama' => 'Operator Sekolah']);
        }
    }
}
