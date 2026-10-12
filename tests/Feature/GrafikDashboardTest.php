<?php

namespace Tests\Feature;

use App\Models\KelasSiswa;
use App\Models\Kepsek;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\PengumpulanTugas;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Services\GrafikService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrafikDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function pengampuDenganSiswa(int $jumlah = 2): Pengampu
    {
        TahunAjaran::aktif() ?? TahunAjaran::factory()->aktif()->create();
        $pengampu = Pengampu::factory()->create();
        foreach (Siswa::factory()->count($jumlah)->create() as $s) {
            KelasSiswa::create(['kelas_id' => $pengampu->kelas_id, 'siswa_id' => $s->id, 'tahun_ajaran_id' => $pengampu->tahun_ajaran_id]);
        }

        return $pengampu;
    }

    public function test_tren_kehadiran_per_minggu_bila_data_kurang_dari_tiga_bulan(): void
    {
        $this->travelTo('2026-10-14 10:00');
        $pengampu = $this->pengampuDenganSiswa(1);
        $siswaId = $pengampu->daftarSiswa()->first()->id;
        foreach ([['2026-09-29', 'hadir'], ['2026-09-30', 'alpa'], ['2026-10-13', 'hadir']] as $ke => [$tanggal, $status]) {
            Presensi::create(['pengampu_id' => $pengampu->id, 'siswa_id' => $siswaId, 'tanggal' => $tanggal, 'pertemuan_ke' => $ke + 1, 'status' => $status]);
        }

        $tren = app(GrafikService::class)->trenKehadiran(collect([$pengampu->id]));

        $this->assertSame(['28 Sep', '5 Okt', '12 Okt'], $tren['label']);
        $this->assertSame([50.0, null, 100.0], $tren['seri'][0]['nilai']);
        $this->assertSame([50.0, null, 0.0], $tren['seri'][1]['nilai']);
    }

    public function test_tren_kehadiran_per_bulan_bila_data_lebih_lama(): void
    {
        $this->travelTo('2026-10-14 10:00');
        $pengampu = $this->pengampuDenganSiswa(1);
        Presensi::create(['pengampu_id' => $pengampu->id, 'siswa_id' => $pengampu->daftarSiswa()->first()->id, 'tanggal' => '2026-07-20', 'pertemuan_ke' => 1, 'status' => 'hadir']);

        $tren = app(GrafikService::class)->trenKehadiran(collect([$pengampu->id]));

        $this->assertSame(['Jul 2026', 'Agt 2026', 'Sep 2026', 'Okt 2026'], $tren['label']);
        $this->assertSame([100.0, null, null, null], $tren['seri'][0]['nilai']);
    }

    public function test_capaian_menghitung_ketuntasan_pengumpulan_dan_kelengkapan_nilai(): void
    {
        $pengampu = $this->pengampuDenganSiswa(2);
        [$a, $b] = $pengampu->daftarSiswa();
        Nilai::create(['pengampu_id' => $pengampu->id, 'siswa_id' => $a->id, 'nilai_akhir' => 80]);
        $tugas = Tugas::factory()->create(['pengampu_id' => $pengampu->id, 'deadline' => now()->subDay()]);
        PengumpulanTugas::factory()->create(['tugas_id' => $tugas->id, 'siswa_id' => $b->id]);

        $capaian = collect(app(GrafikService::class)->capaian(collect([$pengampu->id])))->pluck('nilai', 'label');

        $this->assertNull($capaian['Kehadiran siswa']);
        $this->assertSame(100.0, $capaian['Tuntas KKM']);
        $this->assertSame(50.0, $capaian['Tugas terkumpul']);
        $this->assertSame(50.0, $capaian['Nilai akhir terisi']);
    }

    public function test_dashboard_menampilkan_grafik_untuk_tiga_role(): void
    {
        $pengampu = $this->pengampuDenganSiswa();
        Nilai::create(['pengampu_id' => $pengampu->id, 'siswa_id' => $pengampu->daftarSiswa()->first()->id, 'nilai_akhir' => 70]);

        $this->actingAs(Kepsek::factory()->create()->user)->get(route('kepsek.dashboard'))
            ->assertOk()->assertSee('Rata-rata Nilai per Kelas')->assertSee('grafikBatang', false);
        $this->actingAs($this->operator())->get(route('operator.dashboard'))
            ->assertOk()->assertSee('Siswa per Kelas')->assertSee('grafikBatang', false);
        $this->actingAs($pengampu->guru->user)->get(route('guru.dashboard'))
            ->assertOk()->assertSee('Capaian Kelas Saya');
    }
}
