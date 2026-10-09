<?php

namespace Tests\Feature\Operator;

use App\Models\Jurusan;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Restore menjalankan DROP/CREATE TABLE yang memicu implicit commit di MySQL,
 * sehingga test ini tidak memakai transaksi RefreshDatabase. Database dibuat
 * ulang di awal dan dikosongkan kembali di akhir agar test lain tidak terganggu.
 */
class BackupRestoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--seed' => true, '--seeder' => DummySeeder::class])->run();
    }

    protected function tearDown(): void
    {
        $this->artisan('migrate:fresh')->run();
        RefreshDatabaseState::$migrated = true;

        parent::tearDown();
    }

    public function test_backup_lalu_restore_mengembalikan_data(): void
    {
        $this->actingAs(User::where('username', 'operator')->first());

        $sql = $this->get(route('operator.backup.download'))->assertOk()->streamedContent();
        $this->assertStringContainsString('CREATE TABLE `siswa`', $sql);
        $this->assertStringContainsString('INSERT INTO `siswa`', $sql);

        // Data berubah setelah backup
        Jurusan::create(['kode' => 'BARU', 'nama' => 'Jurusan setelah backup']);

        $path = tempnam(sys_get_temp_dir(), 'bak');
        file_put_contents($path, $sql);
        $file = fn () => new UploadedFile($path, 'backup.sql', 'application/sql', null, true);

        // Konfirmasi ganda wajib
        $this->post(route('operator.backup.restore'), ['file' => $file(), 'konfirmasi_teks' => 'restore', 'password' => 'salah'])
            ->assertSessionHasErrors(['konfirmasi_teks', 'password']);

        $this->post(route('operator.backup.restore'), ['file' => $file(), 'konfirmasi_teks' => 'RESTORE', 'password' => 'password'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('jurusan', ['kode' => 'BARU']);
        $this->assertSame(60, Siswa::count());
        $this->assertGuest();
    }

    public function test_file_selain_sql_ditolak(): void
    {
        $this->actingAs(User::where('username', 'operator')->first());

        $this->post(route('operator.backup.restore'), [
            'file' => UploadedFile::fake()->createWithContent('backup.txt', 'bukan sql'),
            'konfirmasi_teks' => 'RESTORE',
            'password' => 'password',
        ])->assertSessionHasErrors('file');
    }
}
