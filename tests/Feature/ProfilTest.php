<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_semua_role_dapat_membuka_profil(): void
    {
        foreach (['operator', 'kepsek', 'guru', 'siswa', 'ortu'] as $role) {
            $this->actingAs($this->userRole($role))->get('/profil')->assertOk();
        }
    }

    public function test_guru_dapat_memperbarui_profil(): void
    {
        $guru = Guru::factory()->create();

        $this->actingAs($guru->user)->put('/profil', [
            'nama' => 'Nama Baru, S.Pd.',
            'email' => 'guru@contoh.sch.id',
            'no_hp' => '0811',
            'jenis_kelamin' => 'P',
            'alamat' => 'Alamat baru',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Nama Baru, S.Pd.', $guru->fresh()->nama);
        $this->assertSame('guru@contoh.sch.id', $guru->user->fresh()->email);
    }

    public function test_siswa_tidak_dapat_mengubah_nama(): void
    {
        $siswa = Siswa::factory()->create(['nama' => 'Nama Asli']);

        $this->actingAs($siswa->user)->put('/profil', ['nama' => 'Nama Palsu', 'alamat' => 'Baru'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nama Asli', $siswa->fresh()->nama);
        $this->assertSame('Baru', $siswa->fresh()->alamat);
    }

    public function test_ganti_password_memerlukan_password_lama_dan_panjang_minimal(): void
    {
        $user = $this->operator();

        $this->actingAs($user)->put('/profil/password', [
            'password_lama' => 'salah',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors(['password_lama', 'password']);

        $this->put('/profil/password', [
            'password_lama' => 'password',
            'password' => 'passwordbaru',
            'password_confirmation' => 'passwordbaru',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('passwordbaru', $user->fresh()->password));
    }
}
