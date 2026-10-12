<?php

namespace Tests\Feature;

use App\Exports\LaporanExport;
use App\Models\Guru;
use App\Models\KelasSiswa;
use App\Models\Kepsek;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\LaporanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pengampu dengan dua siswa: nilai akhir 80 & 60 (KKM 75), presensi 3 hadir + 1 alpa.
     *
     * @return array{0: Pengampu, 1: Collection<int, Siswa>}
     */
    private function kelasBerdata(): array
    {
        TahunAjaran::aktif() ?? TahunAjaran::factory()->aktif()->create();
        $pengampu = Pengampu::factory()->create();
        $siswa = collect([
            Siswa::factory()->create(['nama' => 'Ani Lestari']),
            Siswa::factory()->create(['nama' => 'Budi Santoso']),
        ]);

        foreach ($siswa as $i => $s) {
            KelasSiswa::create(['kelas_id' => $pengampu->kelas_id, 'siswa_id' => $s->id, 'tahun_ajaran_id' => $pengampu->tahun_ajaran_id]);
            Nilai::create(['pengampu_id' => $pengampu->id, 'siswa_id' => $s->id, 'nilai_tugas' => 70, 'nilai_akhir' => [80, 60][$i]]);
        }
        foreach (['hadir', 'hadir', 'hadir', 'alpa'] as $ke => $status) {
            Presensi::create(['pengampu_id' => $pengampu->id, 'siswa_id' => $siswa[$ke % 2]->id, 'tanggal' => now(), 'pertemuan_ke' => intdiv($ke, 2) + 1, 'status' => $status]);
        }

        return [$pengampu, $siswa];
    }

    public function test_laporan_akademik_menghitung_kehadiran_nilai_dan_ketuntasan(): void
    {
        [$pengampu] = $this->kelasBerdata();

        $laporan = app(LaporanService::class)->akademik($pengampu->tahunAjaran);

        $this->assertSame([1, $pengampu->kelas->nama_kelas, $pengampu->kelas->waliKelas?->nama ?? '-', 2, 75.0, 0, 0, 1, 70.0, 1, 1], $laporan['baris'][0]);
        $this->assertSame('1', $laporan['ringkasan']['Siswa di bawah KKM']);
    }

    public function test_laporan_nilai_kelas_memberi_peringkat_dan_kehadiran_per_siswa(): void
    {
        [$pengampu] = $this->kelasBerdata();
        $service = app(LaporanService::class);

        $nilai = $service->nilaiKelas($pengampu->kelas);
        $this->assertSame(['Ani Lestari', 80.0, 80.0, 1], [$nilai['baris'][0][2], $nilai['baris'][0][3], $nilai['baris'][0][4], $nilai['baris'][0][5]]);
        $this->assertSame(2, $nilai['baris'][1][5]);

        $hadir = $service->kehadiranKelas($pengampu->kelas);
        $this->assertSame(['Budi Santoso', 1, 0, 0, 1, 2, 50.0], array_slice($hadir['baris'][1], 2));
    }

    public function test_operator_dan_kepsek_dapat_membuka_semua_jenis_laporan(): void
    {
        [$pengampu] = $this->kelasBerdata();

        foreach (['operator' => $this->operator(), 'kepsek' => Kepsek::factory()->create()->user] as $prefix => $user) {
            $this->actingAs($user);
            foreach (array_keys(LaporanService::JENIS_SEKOLAH) as $jenis) {
                $this->get(route("$prefix.laporan.index", ['jenis' => $jenis, 'kelas_id' => $pengampu->kelas_id]))->assertOk();
            }
        }

        $this->get(route('kepsek.laporan.index', ['jenis' => 'nilai', 'kelas_id' => $pengampu->kelas_id]))->assertSee('Budi Santoso');
        $this->get(route('kepsek.laporan.index', ['jenis' => 'nilai']))->assertSee('Pilih kelas untuk menampilkan laporan ini.');
    }

    public function test_laporan_tahun_ajaran_lalu_tetap_dapat_dilihat(): void
    {
        [$pengampu] = $this->kelasBerdata();
        $tahunBaru = TahunAjaran::factory()->create();
        $tahunBaru->aktifkan();

        $this->actingAs($this->operator())
            ->get(route('operator.laporan.index', ['jenis' => 'akademik', 'tahun_ajaran_id' => $pengampu->tahun_ajaran_id]))
            ->assertOk()
            ->assertSee($pengampu->kelas->nama_kelas);
    }

    public function test_ekspor_laporan_ke_pdf_dan_excel(): void
    {
        [$pengampu] = $this->kelasBerdata();
        $this->actingAs(Kepsek::factory()->create()->user);

        $this->get(route('kepsek.laporan.ekspor', ['jenis' => 'akademik', 'format' => 'pdf']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        Excel::fake();
        $this->get(route('kepsek.laporan.ekspor', ['jenis' => 'kehadiran', 'kelas_id' => $pengampu->kelas_id, 'format' => 'excel']));
        Excel::assertDownloaded(
            'laporan_kehadiran_kelas_'.str($pengampu->kelas->nama_kelas)->slug('_').'_tahun_ajaran_'.str($pengampu->tahunAjaran->label)->slug('_').'.xlsx',
            fn (LaporanExport $ekspor) => $ekspor->array()[1][2] === 'Budi Santoso',
        );

        $this->get(route('kepsek.laporan.ekspor', ['jenis' => 'akademik', 'format' => 'docx']))->assertSessionHasErrors('format');
    }

    public function test_analisis_ai_laporan_mengirim_data_laporan(): void
    {
        $this->kelasBerdata();
        config(['services.ai.key' => 'kunci-uji', 'services.ai.base_url' => 'https://ai.sumopod.com/v1']);
        Http::preventStrayRequests();
        Http::fake(['https://ai.sumopod.com/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => "### Temuan\n- Kehadiran 75%"]]]])]);

        $this->actingAs(Kepsek::factory()->create()->user)
            ->postJson(route('kepsek.laporan.analisis', ['jenis' => 'akademik']))
            ->assertOk()
            ->assertJsonPath('html', "<h3>Temuan</h3>\n<ul>\n<li>Kehadiran 75%</li>\n</ul>\n");

        Http::assertSent(fn (Request $r) => str_contains($r['messages'][1]['content'], 'Laporan Akademik Semester')
            && str_contains($r['messages'][0]['content'], 'Kepala Sekolah'));
    }

    public function test_role_selain_operator_dan_kepsek_tidak_dapat_membuka_laporan_sekolah(): void
    {
        $this->actingAs($this->userRole('guru'))->get(route('kepsek.laporan.index'))->assertForbidden();
        $this->actingAs($this->userRole('siswa'))->get(route('operator.laporan.index'))->assertForbidden();
    }

    public function test_guru_melihat_laporan_mapel_dan_kelas_walinya(): void
    {
        [$pengampu] = $this->kelasBerdata();
        $pengampu->kelas->update(['wali_kelas_id' => $pengampu->guru_id]);
        $this->actingAs($pengampu->guru->user);

        $this->get(route('guru.laporan.index'))
            ->assertOk()
            ->assertSee('Laporan Nilai '.$pengampu->mapel->nama_mapel)
            ->assertSee('Belum tuntas');

        $this->get(route('guru.laporan.index', ['jenis' => 'kehadiran_kelas']))
            ->assertOk()
            ->assertSee('Laporan Kehadiran Kelas '.$pengampu->kelas->nama_kelas);

        $this->get(route('guru.laporan.ekspor', ['jenis' => 'nilai_mapel', 'format' => 'pdf']))->assertHeader('content-type', 'application/pdf');
    }

    public function test_guru_tidak_dapat_melihat_laporan_mapel_atau_kelas_guru_lain(): void
    {
        [$pengampu] = $this->kelasBerdata();
        $guruLain = Guru::factory()->create();
        Pengampu::factory()->create(['guru_id' => $guruLain->id]);
        $this->actingAs($guruLain->user);

        $this->get(route('guru.laporan.index', ['jenis' => 'nilai_mapel', 'pengampu_id' => $pengampu->id]))
            ->assertOk()
            ->assertDontSee('Budi Santoso');

        $this->get(route('guru.laporan.ekspor', ['jenis' => 'kehadiran_kelas', 'kelas_id' => $pengampu->kelas_id, 'format' => 'pdf']))
            ->assertNotFound();
    }
}
