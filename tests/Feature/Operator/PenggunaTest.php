<?php

namespace Tests\Feature\Operator;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PenggunaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->operator());
    }

    public function test_operator_membuat_guru_dan_guru_bisa_login_dengan_nip(): void
    {
        $this->post(route('operator.guru.store'), [
            'nip' => '198501012010011001',
            'nama' => 'Budi Santoso, S.Kom.',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ])->assertRedirect(route('operator.guru.index'))->assertSessionHas('success');

        $guru = Guru::where('nip', '198501012010011001')->firstOrFail();
        $this->assertSame('198501012010011001', $guru->user->username);
        $this->assertSame('guru', $guru->user->role);
        $this->assertTrue($guru->user->wajib_ganti_password);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'tambah_guru']);

        auth()->logout();
        $this->post('/login', ['username' => '198501012010011001', 'password' => '198501012010011001'])
            ->assertRedirect(route('guru.dashboard'));
    }

    public function test_guru_tanpa_nip_wajib_username(): void
    {
        $this->post(route('operator.guru.store'), ['nama' => 'Tanpa NIP', 'jenis_kelamin' => 'P', 'status' => 'aktif'])
            ->assertSessionHasErrors(['username' => 'Username wajib diisi jika NIP kosong.']);

        $this->post(route('operator.guru.store'), [
            'username' => 'guru.honorer', 'nama' => 'Tanpa NIP', 'jenis_kelamin' => 'P', 'status' => 'aktif',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['username' => 'guru.honorer', 'role' => 'guru']);
    }

    public function test_operator_membuat_siswa_sekaligus_kelasnya(): void
    {
        $kelas = Kelas::factory()->create();

        $this->post(route('operator.siswa.store'), [
            'nis' => '27001', 'nisn' => '0012345678', 'nama' => 'Siti Aminah', 'jenis_kelamin' => 'P',
            'tgl_lahir' => '2010-05-17', 'status' => 'aktif', 'kelas_id' => $kelas->id,
        ])->assertSessionHasNoErrors();

        $siswa = Siswa::where('nis', '27001')->firstOrFail();
        $this->assertSame('27001', $siswa->user->username);
        $this->assertSame($kelas->id, $siswa->kelasPada()?->id);
    }

    public function test_nis_duplikat_ditolak(): void
    {
        Siswa::factory()->create(['nis' => '27001']);

        $this->post(route('operator.siswa.store'), [
            'nis' => '27001', 'nisn' => '0099999999', 'nama' => 'X', 'jenis_kelamin' => 'L', 'status' => 'aktif',
        ])->assertSessionHasErrors('nis');
    }

    public function test_ubah_siswa_memindahkan_kelas_dan_username_mengikuti_nis(): void
    {
        $siswa = Siswa::factory()->create();
        $kelas = Kelas::factory()->create();

        $this->put(route('operator.siswa.update', $siswa), [
            'nis' => '28888', 'nisn' => $siswa->nisn, 'nama' => 'Nama Diubah', 'jenis_kelamin' => 'L',
            'status' => 'aktif', 'kelas_id' => $kelas->id,
        ])->assertSessionHasNoErrors();

        $siswa->refresh();
        $this->assertSame('Nama Diubah', $siswa->nama);
        $this->assertSame('28888', $siswa->user->username);
        $this->assertSame($kelas->id, $siswa->kelasPada()?->id);
    }

    public function test_reset_password_ke_default_dan_wajib_ganti(): void
    {
        $siswa = Siswa::factory()->create(['nis' => '29001']);

        $this->post(route('operator.akun.reset-password', $siswa->user))->assertSessionHas('success');

        $user = $siswa->user->fresh();
        $this->assertTrue(Hash::check('29001', $user->password));
        $this->assertTrue($user->wajib_ganti_password);
    }

    public function test_aktif_nonaktif_akun(): void
    {
        $guru = Guru::factory()->create();

        $this->patch(route('operator.akun.status', $guru->user));
        $this->assertSame('nonaktif', $guru->user->fresh()->status);

        $this->patch(route('operator.akun.status', $guru->user));
        $this->assertSame('aktif', $guru->user->fresh()->status);
    }

    public function test_akun_operator_tidak_bisa_direset_dari_menu_pengguna(): void
    {
        $lain = User::factory()->operator()->create();

        $this->post(route('operator.akun.reset-password', $lain))->assertForbidden();
    }

    public function test_guru_yang_sudah_mengajar_tidak_bisa_dihapus(): void
    {
        $pengampu = Pengampu::factory()->create();

        $this->delete(route('operator.guru.destroy', $pengampu->guru))->assertSessionHas('error');
        $this->assertModelExists($pengampu->guru);

        $guruBaru = Guru::factory()->create();
        $this->delete(route('operator.guru.destroy', $guruBaru))->assertSessionHas('success');
        $this->assertModelMissing($guruBaru);
        $this->assertDatabaseMissing('users', ['id' => $guruBaru->user_id]);
    }

    public function test_kepsek_crud(): void
    {
        $this->post(route('operator.kepsek.store'), [
            'username' => 'kepsek2', 'nip' => '196501011990031001', 'nama' => 'Dr. Kepala',
            'jenis_kelamin' => 'L', 'status' => 'aktif',
        ])->assertSessionHasNoErrors();

        $user = User::where('username', 'kepsek2')->firstOrFail();
        $this->assertTrue(Hash::check('196501011990031001', $user->password));

        $this->put(route('operator.kepsek.update', $user->kepsek), [
            'username' => 'kepsek2', 'nama' => 'Dr. Kepala Baru', 'jenis_kelamin' => 'L', 'status' => 'aktif',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Dr. Kepala Baru', $user->kepsek->fresh()->nama);

        $this->delete(route('operator.kepsek.destroy', $user->kepsek))->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['username' => 'kepsek2']);
    }

    public function test_pencarian_siswa(): void
    {
        TahunAjaran::factory()->aktif()->create();
        Siswa::factory()->create(['nama' => 'Zulkifli Unik']);
        Siswa::factory()->create(['nama' => 'Ani Biasa']);

        $this->get(route('operator.siswa.index', ['q' => 'Zulkifli']))
            ->assertOk()->assertSee('Zulkifli Unik')->assertDontSee('Ani Biasa');
    }
}
