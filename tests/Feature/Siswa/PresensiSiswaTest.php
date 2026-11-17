<?php

namespace Tests\Feature\Siswa;

use App\Models\AbsensiGuru;
use App\Models\Jadwal;
use App\Models\Presensi;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensiSiswaTest extends TestCase
{
    use RefreshDatabase, SiapkanSiswa;

    public function test_siswa_dapat_melihat_jadwal_hari_ini_dan_melakukan_presensi_mandiri_setelah_guru_masuk_kelas(): void
    {
        Carbon::setTestNow('2026-11-17 08:00:00'); // Hari Selasa

        [$pengampu, $siswa] = $this->siswaDiKelas();

        $jadwal = Jadwal::create([
            'pengampu_id' => $pengampu->id,
            'hari' => 'Selasa',
            'jam_mulai' => '07:30:00',
            'jam_selesai' => '09:00:00',
            'ruang' => 'Ruang 101',
        ]);

        // Sebelum guru masuk kelas, siswa melihat status 'Menunggu Guru' dan tidak bisa absen
        $this->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee($pengampu->mapel->nama_mapel)
            ->assertSee('Menunggu Guru');

        $this->get(route('siswa.jadwal'))
            ->assertOk()
            ->assertSee($pengampu->mapel->nama_mapel)
            ->assertSee('Menunggu Guru');

        $this->post(route('siswa.presensi.store'), [
            'pengampu_id' => $pengampu->id,
            'status' => 'hadir',
        ])->assertSessionHasErrors('presensi');

        // Guru melakukan absen masuk kelas, sekaligus membuka presensi siswa
        AbsensiGuru::create([
            'guru_id' => $pengampu->guru_id,
            'jadwal_id' => $jadwal->id,
            'tanggal' => '2026-11-17',
            'jam_masuk' => '07:30:00',
            'status' => 'hadir',
        ]);

        // Setelah guru masuk, tombol 'Absen' muncul bagi siswa
        $this->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee($pengampu->mapel->nama_mapel)
            ->assertSee('Absen');

        $this->get(route('siswa.jadwal'))
            ->assertOk()
            ->assertSee($pengampu->mapel->nama_mapel)
            ->assertSee('Absen');

        // Siswa melakukan absen mandiri
        $response = $this->post(route('siswa.presensi.store'), [
            'pengampu_id' => $pengampu->id,
            'status' => 'hadir',
            'catatan' => 'Hadir tepat waktu',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Data presensi tersimpan dengan benar
        $this->assertDatabaseHas('presensi', [
            'siswa_id' => $siswa->id,
            'pengampu_id' => $pengampu->id,
            'status' => 'hadir',
            'catatan' => 'Hadir tepat waktu',
            'diabsen_oleh' => 'siswa',
        ]);

        // Setelah absen, tombol berganti menjadi badge status
        $this->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('Hadir');

        $this->get(route('siswa.jadwal'))
            ->assertOk()
            ->assertSee('Hadir');

        Carbon::setTestNow();
    }

    public function test_siswa_tidak_dapat_absen_mandiri_dua_kali_di_hari_yang_sama(): void
    {
        Carbon::setTestNow('2026-11-17 08:00:00');

        [$pengampu, $siswa] = $this->siswaDiKelas();

        $jadwal = Jadwal::create([
            'pengampu_id' => $pengampu->id,
            'hari' => 'Selasa',
            'jam_mulai' => '07:30:00',
            'jam_selesai' => '09:00:00',
        ]);

        AbsensiGuru::create([
            'guru_id' => $pengampu->guru_id,
            'jadwal_id' => $jadwal->id,
            'tanggal' => '2026-11-17',
            'jam_masuk' => '07:30:00',
            'status' => 'hadir',
        ]);

        $this->post(route('siswa.presensi.store'), [
            'pengampu_id' => $pengampu->id,
            'status' => 'hadir',
        ])->assertSessionHasNoErrors();

        // Absen kedua kali di hari yang sama harus gagal validasi
        $this->post(route('siswa.presensi.store'), [
            'pengampu_id' => $pengampu->id,
            'status' => 'izin',
        ])->assertSessionHasErrors('presensi');

        $this->assertSame(1, Presensi::where('siswa_id', $siswa->id)->count());

        Carbon::setTestNow();
    }
}
