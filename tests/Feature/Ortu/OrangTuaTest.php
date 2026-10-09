<?php

namespace Tests\Feature\Ortu;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Kuis;
use App\Models\OrangTua;
use App\Models\Pengampu;
use App\Models\PengumpulanTugas;
use App\Models\Pengumuman;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\SuratIzin;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use App\Services\SuratIzinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrangTuaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Siswa terdaftar di kelas sebuah pengampu (tahun aktif).
     */
    private function siswaDi(Pengampu $pengampu): Siswa
    {
        $siswa = Siswa::factory()->create();
        KelasSiswa::create(['kelas_id' => $pengampu->kelas_id, 'siswa_id' => $siswa->id, 'tahun_ajaran_id' => $pengampu->tahun_ajaran_id]);

        return $siswa;
    }

    /**
     * Orang tua (sudah login) dengan satu anak di kelas sebuah pengampu.
     *
     * @return array{0: OrangTua, 1: Siswa, 2: Pengampu}
     */
    private function ortuDenganAnak(): array
    {
        TahunAjaran::aktif() ?? TahunAjaran::factory()->aktif()->create();
        $pengampu = Pengampu::factory()->create();
        $anak = $this->siswaDi($pengampu);
        $ortu = OrangTua::factory()->untuk($anak)->create();

        $this->actingAs($ortu->user);

        return [$ortu, $anak, $pengampu];
    }

    public function test_operator_membuat_akun_orang_tua_dengan_anak(): void
    {
        $this->actingAs($this->operator());
        [$kakak, $adik] = Siswa::factory()->count(2)->create();

        $this->post(route('operator.orang-tua.store'), ['username' => 'budi.ortu', 'nama' => 'Budi', 'hubungan' => 'ayah', 'status' => 'aktif'])
            ->assertSessionHasErrors(['siswa_ids' => 'Pilih minimal satu anak yang dipantau.']);

        $this->post(route('operator.orang-tua.store'), [
            'username' => 'budi.ortu', 'nama' => 'Budi', 'hubungan' => 'ayah', 'status' => 'aktif',
            'siswa_ids' => [$kakak->id, $adik->id],
        ])->assertRedirect(route('operator.orang-tua.index'));

        $user = User::where('username', 'budi.ortu')->firstOrFail();
        $this->assertSame('ortu', $user->role);
        $this->assertTrue($user->wajib_ganti_password);
        $this->assertEqualsCanonicalizing([$kakak->id, $adik->id], $user->ortu->anak->pluck('id')->all());

        $this->put(route('operator.orang-tua.update', $user->ortu), [
            'username' => 'budi.ortu', 'nama' => 'Budi S.', 'hubungan' => 'ayah', 'status' => 'aktif', 'siswa_ids' => [$adik->id],
        ])->assertRedirect(route('operator.orang-tua.index'));

        $this->assertSame([$adik->id], $user->ortu->fresh()->anak->pluck('id')->all());
    }

    public function test_akun_orang_tua_massal_per_kelas_melewati_siswa_yang_sudah_punya(): void
    {
        $this->actingAs($this->operator());
        TahunAjaran::factory()->aktif()->create();
        $pengampu = Pengampu::factory()->create();
        $sudahPunya = $this->siswaDi($pengampu);
        OrangTua::factory()->untuk($sudahPunya)->create();
        $baru = $this->siswaDi($pengampu);
        $baru->update(['nama_ortu' => 'Siti Aminah']);

        $this->post(route('operator.orang-tua.massal'), ['kelas_id' => $pengampu->kelas_id])->assertSessionHas('success');

        $ortu = User::where('username', 'ortu'.$baru->nis)->firstOrFail()->ortu;
        $this->assertSame('Siti Aminah', $ortu->nama);
        $this->assertTrue($ortu->memantau($baru));
        $this->assertSame(2, OrangTua::count());

        $this->post(route('operator.orang-tua.massal'), ['kelas_id' => $pengampu->kelas_id])->assertSessionHas('warning');
    }

    public function test_semua_halaman_orang_tua_dapat_dibuka(): void
    {
        [, $anak, $pengampu] = $this->ortuDenganAnak();
        $tugas = Tugas::factory()->create(['pengampu_id' => $pengampu->id, 'judul' => 'Laporan Praktikum']);
        PengumpulanTugas::factory()->create(['tugas_id' => $tugas->id, 'siswa_id' => $anak->id, 'nilai' => 88]);
        Kuis::factory()->create(['pengampu_id' => $pengampu->id]);
        Presensi::create(['pengampu_id' => $pengampu->id, 'siswa_id' => $anak->id, 'tanggal' => now(), 'pertemuan_ke' => 1, 'status' => 'alpa']);

        foreach (['dashboard', 'nilai', 'presensi', 'tugas', 'kuis', 'jadwal', 'pengumuman', 'surat-izin.index'] as $halaman) {
            $this->get(route("ortu.{$halaman}"))->assertOk();
        }

        $this->get(route('ortu.dashboard'))
            ->assertSee($anak->nama)
            ->assertSee('Laporan Praktikum')
            ->assertSee('Alpa — '.$pengampu->mapel->nama_mapel);
    }

    public function test_orang_tua_tanpa_anak_diarahkan_ke_dashboard(): void
    {
        $this->actingAs(OrangTua::factory()->create()->user);

        $this->get(route('ortu.dashboard'))->assertOk()->assertSee('belum terhubung');
        $this->get(route('ortu.nilai'))->assertRedirect(route('ortu.dashboard'));
    }

    public function test_hanya_dapat_memilih_anak_sendiri(): void
    {
        [$ortu, $kakak, $pengampu] = $this->ortuDenganAnak();
        $adik = $this->siswaDi($pengampu);
        $ortu->anak()->attach($adik);
        $bukanAnak = Siswa::factory()->create();

        $this->post(route('ortu.anak.pilih', $bukanAnak))->assertForbidden();

        $this->post(route('ortu.anak.pilih', $adik));
        $this->get(route('ortu.nilai'))->assertSee('NIS '.$adik->nis);
    }

    public function test_orang_tua_tidak_dapat_mengakses_halaman_role_lain(): void
    {
        $this->ortuDenganAnak();

        $this->get(route('siswa.dashboard'))->assertForbidden();
        $this->get(route('operator.orang-tua.index'))->assertForbidden();
    }

    public function test_mengajukan_surat_izin_untuk_anak(): void
    {
        [$ortu, $anak, $pengampu] = $this->ortuDenganAnak();
        $wali = Guru::factory()->create();
        $pengampu->kelas->update(['wali_kelas_id' => $wali->id]);
        $bukanAnak = Siswa::factory()->create();
        Notification::fake();
        $data = ['jenis' => 'sakit', 'tanggal_mulai' => now()->toDateString(), 'tanggal_selesai' => now()->toDateString(), 'keterangan' => 'Demam'];

        $this->post(route('ortu.surat-izin.store'), $data + ['siswa_id' => $bukanAnak->id])->assertSessionHasErrors('siswa_id');

        $this->post(route('ortu.surat-izin.store'), $data + ['siswa_id' => $anak->id])->assertSessionHas('success');

        $this->assertDatabaseHas('surat_izin', ['siswa_id' => $anak->id, 'pengaju_user_id' => $ortu->user_id, 'status' => 'menunggu']);
        Notification::assertSentTo($wali->user, Pemberitahuan::class);
    }

    public function test_hanya_surat_yang_diajukan_sendiri_dapat_dibatalkan(): void
    {
        [$ortu, $anak, $pengampu] = $this->ortuDenganAnak();
        $olehAnak = SuratIzin::factory()->create(['siswa_id' => $anak->id, 'kelas_id' => $pengampu->kelas_id, 'pengaju_user_id' => $anak->user_id]);
        $olehOrtu = SuratIzin::factory()->create(['siswa_id' => $anak->id, 'kelas_id' => $pengampu->kelas_id, 'pengaju_user_id' => $ortu->user_id]);

        $this->delete(route('ortu.surat-izin.destroy', $olehAnak))->assertForbidden();
        $this->delete(route('ortu.surat-izin.destroy', $olehOrtu))->assertSessionHas('success');

        $this->assertModelExists($olehAnak);
        $this->assertModelMissing($olehOrtu);
    }

    public function test_orang_tua_diberi_tahu_saat_surat_izin_diproses(): void
    {
        [$ortu, $anak, $pengampu] = $this->ortuDenganAnak();
        $wali = Guru::factory()->create();
        $surat = SuratIzin::factory()->create(['siswa_id' => $anak->id, 'kelas_id' => $pengampu->kelas_id]);
        Notification::fake();

        app(SuratIzinService::class)->proses($surat, 'disetujui', null, $wali);

        Notification::assertSentTo($ortu->user, Pemberitahuan::class);
        Notification::assertSentTo($anak->user, Pemberitahuan::class);
    }

    public function test_pengumuman_yang_tampil_sesuai_target_orang_tua(): void
    {
        [, , $pengampu] = $this->ortuDenganAnak();
        $pembuat = $this->operator();
        $buat = fn (string $judul, string $target, ?int $kelasId = null) => Pengumuman::create([
            'pembuat_user_id' => $pembuat->id, 'judul' => $judul, 'isi' => 'Isi', 'target' => $target, 'kelas_id' => $kelasId,
        ]);

        $buat('Untuk Semua', 'semua');
        $buat('Rapat Wali Murid', 'ortu');
        $buat('Kelas Anak', 'kelas', $pengampu->kelas_id);
        $buat('Khusus Siswa', 'siswa');
        $buat('Khusus Guru', 'guru');
        $buat('Kelas Lain', 'kelas', Kelas::factory()->create()->id);

        $this->get(route('ortu.pengumuman'))
            ->assertSee('Untuk Semua')
            ->assertSee('Rapat Wali Murid')
            ->assertSee('Kelas Anak')
            ->assertDontSee('Khusus Siswa')
            ->assertDontSee('Khusus Guru')
            ->assertDontSee('Kelas Lain');
    }

    public function test_pengumuman_kepsek_untuk_orang_tua_hanya_dikirim_ke_orang_tua(): void
    {
        TahunAjaran::factory()->aktif()->create();
        $kepsek = $this->userRole('kepsek');
        $ortu = OrangTua::factory()->create()->user;
        $siswa = Siswa::factory()->create()->user;
        Notification::fake();

        $this->actingAs($kepsek)->post(route('kepsek.pengumuman.store'), ['judul' => 'Rapat', 'isi' => 'Sabtu', 'target' => 'ortu'])
            ->assertSessionHas('success');

        Notification::assertSentTo($ortu, Pemberitahuan::class);
        Notification::assertNotSentTo($siswa, Pemberitahuan::class);
    }
}
