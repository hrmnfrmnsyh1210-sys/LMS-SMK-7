<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Kriteria selesai Fase 1: operator membuat data dari nol
 * (tahun ajaran → jurusan → kelas → mapel → guru/siswa via import →
 * plotting → jadwal) lalu login sebagai guru/siswa hasil buatannya.
 */
class Fase1AlurOperatorTest extends TestCase
{
    use RefreshDatabase;

    private function xlsx(array $baris): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        foreach ($baris as $r => $kolom) {
            foreach (array_values($kolom) as $c => $nilai) {
                $spreadsheet->getActiveSheet()->setCellValueExplicit([$c + 1, $r + 1], $nilai, DataType::TYPE_STRING);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'data.xlsx', null, null, true);
    }

    public function test_operator_membangun_data_dari_nol_hingga_guru_dan_siswa_bisa_login(): void
    {
        $operator = $this->operator();
        $this->actingAs($operator);

        // 1. Tahun ajaran
        $this->post(route('operator.tahun-ajaran.store'), ['nama' => '2026/2027', 'semester' => 'ganjil'])->assertSessionHasNoErrors();
        $this->assertTrue(TahunAjaran::first()->status_aktif);

        // 2. Jurusan
        $this->post(route('operator.jurusan.store'), ['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak'])->assertSessionHasNoErrors();

        // 3. Kelas
        $this->post(route('operator.kelas.store'), [
            'nama_kelas' => 'X RPL 1', 'tingkat' => 'X', 'jurusan_id' => Jurusan::first()->id,
        ])->assertSessionHasNoErrors();
        $kelas = Kelas::first();

        // 4. Mapel
        $this->post(route('operator.mapel.store'), ['kode' => 'MTK', 'nama_mapel' => 'Matematika'])->assertSessionHasNoErrors();

        // 5. Guru & siswa via import
        $this->post(route('operator.import.store', 'guru'), ['file' => $this->xlsx([
            ['nip', 'username', 'nama', 'jenis_kelamin', 'no_hp', 'alamat', 'email'],
            ['198001012010011001', '', 'Bambang Sutrisno, S.Pd.', 'L', '', '', ''],
        ])]);
        $this->assertSame(1, session('hasil_import')['berhasil']);

        $this->post(route('operator.import.store', 'siswa'), ['file' => $this->xlsx([
            ['nis', 'nisn', 'nama', 'jenis_kelamin', 'tgl_lahir', 'alamat', 'nama_ortu', 'kelas'],
            ['26001', '0091000001', 'Siti Aminah', 'P', '2010-05-17', '', '', 'X RPL 1'],
            ['26002', '0091000002', 'Rudi Hartono', 'L', '2010-02-01', '', '', 'X RPL 1'],
        ])]);
        $this->assertSame(2, session('hasil_import')['berhasil']);
        $this->assertSame(2, $kelas->siswa()->count());

        // 6. Plotting pengampu + wali kelas
        $guru = Guru::first();
        $this->post(route('operator.pengampu.store'), [
            'guru_id' => $guru->id, 'mapel_id' => Mapel::first()->id, 'kelas_id' => $kelas->id,
        ])->assertSessionHasNoErrors();
        $this->put(route('operator.wali-kelas.update'), ['wali' => [$kelas->id => $guru->id]])->assertSessionHas('success');

        // 7. Jadwal
        $this->post(route('operator.jadwal.store'), [
            'pengampu_id' => Pengampu::first()->id, 'hari' => 'Senin', 'jam_mulai' => '07:00', 'jam_selesai' => '08:30', 'ruang' => 'Lab 1',
        ])->assertSessionHasNoErrors();

        $this->get(route('operator.jadwal.index', ['mode' => 'kelas', 'id' => $kelas->id]))
            ->assertOk()->assertSee('Matematika')->assertSee('Bambang Sutrisno');

        $this->post('/logout');

        // 8. Login sebagai guru hasil import (password awal = NIP) → wajib ganti password
        $this->post('/login', ['username' => '198001012010011001', 'password' => '198001012010011001'])
            ->assertRedirect(route('guru.dashboard'));
        $this->get(route('guru.dashboard'))->assertRedirect(route('profil.password'));
        $this->put(route('profil.password.update'), [
            'password_lama' => '198001012010011001', 'password' => 'guru-baru-123', 'password_confirmation' => 'guru-baru-123',
        ]);
        $this->get(route('guru.dashboard'))->assertOk()->assertSee('Bambang Sutrisno');
        $this->post('/logout');

        // 9. Login sebagai siswa hasil import (password awal = NIS)
        $this->post('/login', ['username' => '26001', 'password' => '26001'])->assertRedirect(route('siswa.dashboard'));
        $this->assertAuthenticatedAs(Siswa::where('nis', '26001')->first()->user);
    }
}
