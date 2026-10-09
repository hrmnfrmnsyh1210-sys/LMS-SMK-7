<?php

namespace Tests\Feature\Operator;

use App\Models\Jadwal;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SistemTest extends TestCase
{
    use RefreshDatabase;

    public function test_kenaikan_kelas_dengan_kelas_tujuan_otomatis_dan_kelulusan(): void
    {
        $this->actingAs($this->operator());
        $lama = TahunAjaran::factory()->aktif()->create(['nama' => '2026/2027', 'semester' => 'genap']);
        $baru = TahunAjaran::factory()->create(['nama' => '2027/2028', 'semester' => 'ganjil']);
        $rpl = Jurusan::factory()->create(['kode' => 'RPL']);

        $kelasX = Kelas::factory()->create(['nama_kelas' => 'X RPL 1', 'tingkat' => 'X', 'jurusan_id' => $rpl->id, 'tahun_ajaran_id' => $lama->id]);
        $kelasXII = Kelas::factory()->create(['nama_kelas' => 'XII RPL 1', 'tingkat' => 'XII', 'jurusan_id' => $rpl->id, 'tahun_ajaran_id' => $lama->id]);

        $siswaX = Siswa::factory()->count(2)->create();
        $siswaXII = Siswa::factory()->create();
        foreach ($siswaX as $s) {
            KelasSiswa::create(['kelas_id' => $kelasX->id, 'siswa_id' => $s->id, 'tahun_ajaran_id' => $lama->id]);
        }
        KelasSiswa::create(['kelas_id' => $kelasXII->id, 'siswa_id' => $siswaXII->id, 'tahun_ajaran_id' => $lama->id]);

        $this->post(route('operator.kenaikan-kelas.buat-kelas'), ['sumber_id' => $lama->id, 'tujuan_id' => $baru->id])
            ->assertSessionHas('success');
        $kelasXI = Kelas::where('tahun_ajaran_id', $baru->id)->where('nama_kelas', 'XI RPL 1')->firstOrFail();
        $this->assertSame('XI', $kelasXI->tingkat);

        $this->get(route('operator.kenaikan-kelas.index', ['sumber_id' => $lama->id, 'tujuan_id' => $baru->id]))
            ->assertOk()->assertSee('XI RPL 1');

        // Tanpa konfirmasi ditolak
        $this->post(route('operator.kenaikan-kelas.proses'), [
            'sumber_id' => $lama->id, 'tujuan_id' => $baru->id, 'pemetaan' => [$kelasX->id => $kelasXI->id],
        ])->assertSessionHasErrors('konfirmasi');

        $this->post(route('operator.kenaikan-kelas.proses'), [
            'sumber_id' => $lama->id, 'tujuan_id' => $baru->id, 'konfirmasi' => 1,
            'pemetaan' => [$kelasX->id => $kelasXI->id, $kelasXII->id => 'lulus'],
        ])->assertSessionHas('success', 'Kenaikan kelas selesai: 2 siswa naik kelas, 1 siswa lulus, 0 siswa dilewati.');

        foreach ($siswaX as $s) {
            $this->assertSame($kelasXI->id, $s->kelasPada($baru->id)?->id);
            // Riwayat kelas lama tetap ada
            $this->assertSame($kelasX->id, $s->kelasPada($lama->id)?->id);
        }
        $this->assertSame('lulus', $siswaXII->fresh()->status);
        $this->assertSame('nonaktif', $siswaXII->user->fresh()->status);
    }

    public function test_pergantian_semester_menyalin_kelas_anggota_pengampu_dan_jadwal(): void
    {
        $this->seed(DummySeeder::class);
        $this->actingAs(User::where('username', 'operator')->first());

        $ganjil = TahunAjaran::aktif();
        $genap = TahunAjaran::create(['nama' => '2026/2027', 'semester' => 'genap']);

        $this->post(route('operator.pergantian-tahun-ajaran.store'), ['tujuan_id' => $genap->id, 'salin_data' => 1])
            ->assertSessionHasErrors('konfirmasi');

        $this->post(route('operator.pergantian-tahun-ajaran.store'), ['tujuan_id' => $genap->id, 'salin_data' => 1, 'konfirmasi' => 1])
            ->assertSessionHas('success');

        $this->assertTrue($genap->fresh()->status_aktif);
        $this->assertFalse($ganjil->fresh()->status_aktif);
        $this->assertSame(6, Kelas::where('tahun_ajaran_id', $genap->id)->count());
        $this->assertSame(60, KelasSiswa::where('tahun_ajaran_id', $genap->id)->count());
        $this->assertSame(60, Pengampu::where('tahun_ajaran_id', $genap->id)->count());
        $this->assertSame(120, Jadwal::count());

        // Data tahun lama tetap utuh
        $this->assertSame(60, Pengampu::where('tahun_ajaran_id', $ganjil->id)->count());
    }

    public function test_log_aktivitas_dapat_difilter(): void
    {
        $operator = $this->operator();
        $this->actingAs($operator)->post(route('operator.jurusan.store'), ['kode' => 'TKJ', 'nama' => 'Teknik Komputer']);

        $this->get(route('operator.log.index', ['aksi' => 'tambah_jurusan']))
            ->assertOk()->assertSee('Menambah jurusan Teknik Komputer');
        $this->get(route('operator.log.index', ['aksi' => 'login']))
            ->assertOk()->assertDontSee('Menambah jurusan Teknik Komputer');
    }
}
