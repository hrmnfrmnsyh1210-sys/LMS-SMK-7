<?php

namespace Tests\Feature\Auth;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_dapat_dibuka(): void
    {
        $this->get('/login')->assertOk()->assertSee('Username');
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/operator')->assertRedirect('/login');
        $this->get('/')->assertRedirect('/login');
    }

    public static function daftarRole(): array
    {
        return [['operator'], ['kepsek'], ['guru'], ['siswa'], ['ortu']];
    }

    #[DataProvider('daftarRole')]
    public function test_login_dengan_username_diarahkan_ke_dashboard_role(string $role): void
    {
        $user = $this->userRole($role);

        $this->post('/login', ['username' => $user->username, 'password' => 'password'])
            ->assertRedirect(route("{$role}.dashboard"));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login);
        $this->assertDatabaseHas('log_aktivitas', ['user_id' => $user->id, 'aksi' => 'login']);

        $this->get(route("{$role}.dashboard"))->assertOk();
    }

    public function test_password_salah_ditolak(): void
    {
        $user = $this->operator();

        $this->from('/login')
            ->post('/login', ['username' => $user->username, 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['username' => 'Username atau password salah.']);

        $this->assertGuest();
    }

    public function test_akun_nonaktif_tidak_bisa_login(): void
    {
        $user = User::factory()->operator()->nonaktif()->create();

        $this->post('/login', ['username' => $user->username, 'password' => 'password'])
            ->assertSessionHasErrors(['username' => 'Akun Anda dinonaktifkan. Silakan hubungi operator sekolah.']);

        $this->assertGuest();
        $this->assertSame(0, LogAktivitas::where('aksi', 'login')->count());
    }

    public function test_akun_yang_dinonaktifkan_saat_sesi_berjalan_dikeluarkan(): void
    {
        $user = $this->operator();
        $this->actingAs($user);
        $user->update(['status' => 'nonaktif']);

        $this->get('/operator')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_role_lain_tidak_bisa_mengakses_halaman_operator(): void
    {
        $this->actingAs($this->userRole('guru'))->get('/operator/guru')->assertForbidden();
        $this->actingAs($this->userRole('siswa'))->get('/operator')->assertForbidden();
        $this->actingAs($this->operator())->get('/guru')->assertForbidden();
    }

    public function test_dashboard_mengarahkan_sesuai_role(): void
    {
        $this->actingAs($this->userRole('siswa'))->get('/dashboard')->assertRedirect(route('siswa.dashboard'));
    }

    public function test_wajib_ganti_password_diarahkan_ke_halaman_ganti_password(): void
    {
        $user = User::factory()->operator()->wajibGantiPassword()->create();

        $this->actingAs($user)->get('/operator')->assertRedirect(route('profil.password'));

        $this->put(route('profil.password.update'), [
            'password_lama' => 'password',
            'password' => 'rahasia-baru-123',
            'password_confirmation' => 'rahasia-baru-123',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse($user->fresh()->wajib_ganti_password);
        $this->get('/operator')->assertOk();
    }

    public function test_logout(): void
    {
        $this->actingAs($this->operator())->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
