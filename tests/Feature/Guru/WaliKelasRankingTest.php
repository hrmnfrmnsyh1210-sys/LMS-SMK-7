<?php

namespace Tests\Feature\Guru;

use App\Models\Mapel;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Services\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaliKelasRankingTest extends TestCase
{
    use RefreshDatabase, SiapkanKelasAjar;

    public function test_ranking_kelas_dari_rata_rata_nilai_akhir_semua_mapel_dengan_nilai_sama_berperingkat_sama(): void
    {
        [$pengampuA, $siswa] = $this->kelasAjar(4);
        $pengampuB = Pengampu::factory()->create([
            'kelas_id' => $pengampuA->kelas_id, 'mapel_id' => Mapel::factory(), 'tahun_ajaran_id' => $pengampuA->tahun_ajaran_id,
        ]);

        // Rata-rata: s0 = 90, s1 = 80, s2 = 90, s3 belum punya nilai
        foreach ([[$siswa[0], 85, 95], [$siswa[1], 80, 80], [$siswa[2], 90, 90]] as [$s, $a, $b]) {
            Nilai::create(['pengampu_id' => $pengampuA->id, 'siswa_id' => $s->id, 'nilai_akhir' => $a]);
            Nilai::create(['pengampu_id' => $pengampuB->id, 'siswa_id' => $s->id, 'nilai_akhir' => $b]);
        }

        $ranking = app(RankingService::class)->kelas($pengampuA->kelas)->keyBy(fn ($r) => $r['siswa']->id);

        $this->assertSame(1, $ranking[$siswa[0]->id]['peringkat']);
        $this->assertSame(1, $ranking[$siswa[2]->id]['peringkat']);
        $this->assertSame(3, $ranking[$siswa[1]->id]['peringkat']);
        $this->assertNull($ranking[$siswa[3]->id]['peringkat']);
        $this->assertEquals(90, $ranking[$siswa[0]->id]['rata']);
    }

    public function test_wali_kelas_melihat_rekap_dan_menu_wali(): void
    {
        [$pengampu, $siswa] = $this->kelasAjar(2);
        $pengampu->kelas->update(['wali_kelas_id' => $pengampu->guru_id]);
        Nilai::create(['pengampu_id' => $pengampu->id, 'siswa_id' => $siswa[0]->id, 'nilai_akhir' => 88.5]);

        $this->get(route('guru.dashboard'))->assertOk()->assertSee('Surat Izin');
        $this->get(route('guru.wali.rekap'))->assertOk()->assertSee($siswa[0]->nama)->assertSee('88.5');
    }
}
