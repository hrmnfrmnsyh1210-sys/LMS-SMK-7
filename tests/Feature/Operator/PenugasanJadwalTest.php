<?php

namespace Tests\Feature\Operator;

use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Services\JadwalService;
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenugasanJadwalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->operator());
    }

    public function test_plotting_pengampu_dan_mapel_kelas_tidak_boleh_ganda(): void
    {
        $kelas = Kelas::factory()->create();
        $mapel = Mapel::factory()->create();
        [$guruA, $guruB] = Guru::factory()->count(2)->create();

        $this->post(route('operator.pengampu.store'), [
            'guru_id' => $guruA->id, 'mapel_id' => $mapel->id, 'kelas_id' => $kelas->id,
        ])->assertSessionHasNoErrors();

        $this->post(route('operator.pengampu.store'), [
            'guru_id' => $guruB->id, 'mapel_id' => $mapel->id, 'kelas_id' => $kelas->id,
        ])->assertSessionHasErrors(['mapel_id' => 'Mata pelajaran ini sudah memiliki pengampu di kelas tersebut.']);

        $this->assertSame(1, Pengampu::count());
    }

    public function test_memasukkan_banyak_siswa_ke_kelas_sekaligus(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->count(3)->create();

        $this->post(route('operator.kelas.siswa.store', $kelas), ['siswa_id' => $siswa->pluck('id')->all()])
            ->assertSessionHas('success');
        $this->assertSame(3, $kelas->siswa()->count());

        // Siswa yang sudah punya kelas di tahun yang sama ditolak
        $kelasLain = Kelas::factory()->create(['tahun_ajaran_id' => $kelas->tahun_ajaran_id]);
        $this->post(route('operator.kelas.siswa.store', $kelasLain), ['siswa_id' => [$siswa[0]->id]])
            ->assertSessionHasErrors('siswa_id.0');

        $this->delete(route('operator.kelas.siswa.destroy', [$kelas, $siswa[0]]));
        $this->assertSame(2, $kelas->siswa()->count());
    }

    public function test_wali_kelas_satu_guru_satu_kelas(): void
    {
        [$k1, $k2] = Kelas::factory()->count(2)->create();
        $guru = Guru::factory()->create();

        $this->put(route('operator.wali-kelas.update'), ['wali' => [$k1->id => $guru->id, $k2->id => $guru->id]])
            ->assertSessionHas('error');

        $this->put(route('operator.wali-kelas.update'), ['wali' => [$k1->id => $guru->id, $k2->id => '']])
            ->assertSessionHas('success');
        $this->assertSame($guru->id, $k1->fresh()->wali_kelas_id);
        $this->assertNull($k2->fresh()->wali_kelas_id);
    }

    public function test_jadwal_bentrok_kelas_dan_guru_ditolak(): void
    {
        $p1 = Pengampu::factory()->create();
        // Kelas sama, guru lain
        $p2 = Pengampu::factory()->create(['kelas_id' => $p1->kelas_id]);
        // Guru sama, kelas lain
        $p3 = Pengampu::factory()->create(['guru_id' => $p1->guru_id]);

        $data = ['hari' => 'Senin', 'jam_mulai' => '07:00', 'jam_selesai' => '08:30', 'ruang' => 'R1'];

        $this->post(route('operator.jadwal.store'), ['pengampu_id' => $p1->id] + $data)->assertSessionHasNoErrors();

        $this->post(route('operator.jadwal.store'), ['pengampu_id' => $p2->id, 'jam_mulai' => '08:00', 'jam_selesai' => '09:00'] + $data)
            ->assertSessionHasErrors('jam_mulai');
        $this->post(route('operator.jadwal.store'), ['pengampu_id' => $p3->id, 'jam_mulai' => '06:30', 'jam_selesai' => '07:30'] + $data)
            ->assertSessionHasErrors('jam_mulai');

        // Bersebelahan (tidak beririsan) diperbolehkan
        $this->post(route('operator.jadwal.store'), ['pengampu_id' => $p2->id, 'jam_mulai' => '08:30', 'jam_selesai' => '10:00'] + $data)
            ->assertSessionHasNoErrors();
        // Hari lain diperbolehkan
        $this->post(route('operator.jadwal.store'), ['pengampu_id' => $p3->id, 'hari' => 'Selasa'] + $data)
            ->assertSessionHasNoErrors();

        $this->assertSame(3, Jadwal::count());
    }

    public function test_ubah_jadwal_tidak_bentrok_dengan_dirinya_sendiri(): void
    {
        $p = Pengampu::factory()->create();
        $jadwal = Jadwal::create(['pengampu_id' => $p->id, 'hari' => 'Rabu', 'jam_mulai' => '07:00', 'jam_selesai' => '08:30']);

        $this->put(route('operator.jadwal.update', $jadwal), [
            'pengampu_id' => $p->id, 'hari' => 'Rabu', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00',
        ])->assertSessionHasNoErrors();

        $this->assertSame('07:30:00', $jadwal->fresh()->jam_mulai);
    }

    public function test_jam_selesai_harus_setelah_jam_mulai(): void
    {
        $p = Pengampu::factory()->create();

        $this->post(route('operator.jadwal.store'), [
            'pengampu_id' => $p->id, 'hari' => 'Senin', 'jam_mulai' => '09:00', 'jam_selesai' => '08:00',
        ])->assertSessionHasErrors('jam_selesai');
    }

    public function test_jadwal_dummy_tidak_ada_yang_bentrok(): void
    {
        $this->seed(DummySeeder::class);
        $service = app(JadwalService::class);

        foreach (Jadwal::with('pengampu')->get() as $j) {
            $this->assertNull(
                $service->cekBentrok($j->pengampu, $j->hari, $j->jam_mulai, $j->jam_selesai, $j->id),
                "Jadwal #{$j->id} bentrok"
            );
        }
    }
}
