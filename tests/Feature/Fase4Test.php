<?php

namespace Tests\Feature;

use App\Models\KelasSiswa;
use App\Models\Kuis;
use App\Models\OrangTua;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\TahunAjaran;
use App\Notifications\Pemberitahuan;
use App\Services\PengerjaanKuisService;
use App\Services\PresensiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Kesiapan produksi: halaman error, header keamanan, tugas terjadwal, dan notifikasi alpa. */
class Fase4Test extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_404_berbahasa_indonesia(): void
    {
        $this->actingAs($this->operator())
            ->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan');
    }

    public function test_halaman_403_menampilkan_pesan_aplikasi(): void
    {
        $this->actingAs(Siswa::factory()->create()->user)
            ->get(route('operator.dashboard'))
            ->assertForbidden()
            ->assertSee('Anda tidak memiliki akses ke halaman ini.');
    }

    public function test_respons_web_membawa_header_keamanan(): void
    {
        $this->get(route('login'))
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_tutup_kuis_mengirim_pengerjaan_yang_waktunya_habis_saja(): void
    {
        TahunAjaran::factory()->aktif()->create();
        $kuis = Kuis::factory()->create(['durasi_menit' => 10]);
        $soal = Soal::factory()->create(['kuis_id' => $kuis->id, 'kunci_jawaban' => 'a']);
        $layanan = app(PengerjaanKuisService::class);

        $kedaluwarsa = $layanan->mulai($kuis, Siswa::factory()->create());
        $layanan->simpanJawaban($kedaluwarsa, $soal, 'a');
        $kedaluwarsa->update(['waktu_mulai' => now()->subMinutes(15)]);
        $berjalan = $layanan->mulai($kuis, Siswa::factory()->create());

        $this->artisan('lms:tutup-kuis')->expectsOutput('1 pengerjaan kuis dikirim otomatis.')->assertSuccessful();

        $this->assertNotNull($kedaluwarsa->fresh()->waktu_submit);
        $this->assertEquals(100, $kedaluwarsa->fresh()->total_skor);
        $this->assertNull($berjalan->fresh()->waktu_submit);
    }

    public function test_bersihkan_notifikasi_lama(): void
    {
        $user = $this->operator();
        $user->notify(new Pemberitahuan('lama dibaca'));
        $user->notify(new Pemberitahuan('baru dibaca'));
        $user->notify(new Pemberitahuan('lama belum dibaca'));
        $notif = $user->notifications()->get()->keyBy('data.pesan');
        $notif['lama dibaca']->forceFill(['read_at' => now(), 'created_at' => now()->subDays(61)])->save();
        $notif['baru dibaca']->forceFill(['read_at' => now()])->save();
        $notif['lama belum dibaca']->forceFill(['created_at' => now()->subDays(181)])->save();

        $this->artisan('lms:bersihkan-notifikasi')->assertSuccessful();

        $this->assertSame(['baru dibaca'], $user->notifications()->get()->pluck('data.pesan')->all());
    }

    public function test_orang_tua_diberi_tahu_sekali_saat_anak_baru_tercatat_alpa(): void
    {
        TahunAjaran::factory()->aktif()->create();
        $pengampu = Pengampu::factory()->create();
        [$alpa, $hadir] = Siswa::factory()->count(2)->create();
        foreach ([$alpa, $hadir] as $s) {
            KelasSiswa::create(['kelas_id' => $pengampu->kelas_id, 'siswa_id' => $s->id, 'tahun_ajaran_id' => $pengampu->tahun_ajaran_id]);
        }
        $ortuAlpa = OrangTua::factory()->untuk($alpa)->create()->user;
        $ortuHadir = OrangTua::factory()->untuk($hadir)->create()->user;
        Notification::fake();

        $status = [$alpa->id => 'alpa', $hadir->id => 'hadir'];
        app(PresensiService::class)->simpan($pengampu, 1, now()->toDateString(), $status);
        app(PresensiService::class)->simpan($pengampu, 1, now()->toDateString(), $status);

        Notification::assertSentToTimes($ortuAlpa, Pemberitahuan::class, 1);
        Notification::assertNotSentTo($ortuHadir, Pemberitahuan::class);
    }

    public function test_jadwal_tugas_otomatis_terdaftar(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('lms:tutup-kuis')
            ->expectsOutputToContain('lms:bersihkan-notifikasi')
            ->assertSuccessful();
    }
}
