<?php

namespace Tests\Feature\Kepsek;

use App\Models\Guru;
use App\Models\KelasSiswa;
use App\Models\Kepsek;
use App\Models\Materi;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Pengumuman;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use App\Services\MonitoringSekolahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class KepsekTest extends TestCase
{
    use RefreshDatabase;

    private function kepsek(): User
    {
        TahunAjaran::aktif() ?? TahunAjaran::factory()->aktif()->create();

        return Kepsek::factory()->create()->user;
    }

    public function test_semua_halaman_kepsek_dapat_dibuka(): void
    {
        $this->actingAs($this->kepsek());
        $pengampu = Pengampu::factory()->create();

        foreach ([
            route('kepsek.dashboard'),
            route('kepsek.monitoring.guru'),
            route('kepsek.monitoring.kelas'),
            route('kepsek.monitoring.kelas.show', $pengampu->kelas),
            route('kepsek.pengumuman.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_role_lain_tidak_dapat_mengakses_halaman_kepsek(): void
    {
        $this->actingAs(Siswa::factory()->create()->user)
            ->get(route('kepsek.dashboard'))
            ->assertForbidden();
    }

    public function test_rekap_kelas_menghitung_kehadiran_dan_rata_nilai(): void
    {
        $this->actingAs($this->kepsek());
        $pengampu = Pengampu::factory()->create();
        $siswa = Siswa::factory()->count(2)->create();
        foreach ($siswa as $i => $s) {
            KelasSiswa::create(['kelas_id' => $pengampu->kelas_id, 'siswa_id' => $s->id, 'tahun_ajaran_id' => $pengampu->tahun_ajaran_id]);
            Nilai::create(['pengampu_id' => $pengampu->id, 'siswa_id' => $s->id, 'nilai_akhir' => [80, 90][$i]]);
        }
        foreach (['hadir', 'hadir', 'hadir', 'alpa'] as $ke => $status) {
            Presensi::create(['pengampu_id' => $pengampu->id, 'siswa_id' => $siswa[$ke % 2]->id, 'tanggal' => now(), 'pertemuan_ke' => intdiv($ke, 2) + 1, 'status' => $status]);
        }

        $this->get(route('kepsek.monitoring.kelas'))
            ->assertOk()
            ->assertSee('75%')
            ->assertSee('85');
    }

    public function test_monitoring_guru_menghitung_konten_tahun_aktif(): void
    {
        $this->actingAs($this->kepsek());
        $pengampu = Pengampu::factory()->create();
        Materi::factory()->count(3)->create(['pengampu_id' => $pengampu->id]);
        $tanpaKelas = Guru::factory()->create();

        $rekap = app(MonitoringSekolahService::class)->rekapGuru();
        $baris = $rekap->firstWhere(fn ($r) => $r['guru']->id === $pengampu->guru_id);

        $this->assertSame(1, $baris['kelas']);
        $this->assertSame(3, $baris['materi']);
        $this->assertSame(0, $rekap->firstWhere(fn ($r) => $r['guru']->id === $tanpaKelas->id)['kelas']);
    }

    public function test_pengumuman_untuk_siswa_hanya_dikirim_ke_siswa(): void
    {
        $kepsek = $this->kepsek();
        $guru = Guru::factory()->create()->user;
        $siswa = Siswa::factory()->create()->user;
        Notification::fake();

        $this->actingAs($kepsek)->post(route('kepsek.pengumuman.store'), [])->assertSessionHasErrors(['judul', 'isi', 'target']);
        $this->post(route('kepsek.pengumuman.store'), ['judul' => 'Libur', 'isi' => 'Besok libur', 'target' => 'siswa'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pengumuman', ['judul' => 'Libur', 'target' => 'siswa', 'pembuat_user_id' => $kepsek->id]);
        Notification::assertSentTo($siswa, Pemberitahuan::class);
        Notification::assertNotSentTo($guru, Pemberitahuan::class);
    }

    public function test_kepsek_hanya_dapat_menghapus_pengumuman_sendiri(): void
    {
        $kepsek = $this->kepsek();
        $milikOperator = Pengumuman::create(['pembuat_user_id' => $this->operator()->id, 'judul' => 'A', 'isi' => 'B', 'target' => 'semua']);
        $milikSendiri = Pengumuman::create(['pembuat_user_id' => $kepsek->id, 'judul' => 'C', 'isi' => 'D', 'target' => 'guru']);

        $this->actingAs($kepsek)->delete(route('kepsek.pengumuman.destroy', $milikOperator))->assertForbidden();
        $this->delete(route('kepsek.pengumuman.destroy', $milikSendiri))->assertSessionHas('success');

        $this->assertModelExists($milikOperator);
        $this->assertModelMissing($milikSendiri);
    }
}
