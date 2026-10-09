<?php

namespace Tests\Feature\Operator;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    /** Buat file .xlsx sementara dari array baris (baris pertama = heading). */
    private function xlsx(array $baris): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($baris as $r => $kolom) {
            foreach (array_values($kolom) as $c => $nilai) {
                $sheet->setCellValueExplicit([$c + 1, $r + 1], $nilai, DataType::TYPE_STRING);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'data.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_import_siswa_menyimpan_baris_valid_dan_melaporkan_baris_gagal(): void
    {
        $kelas = Kelas::factory()->create(['nama_kelas' => 'X RPL 1']);
        Siswa::factory()->create(['nis' => '26999']);

        $file = $this->xlsx([
            ['nis', 'nisn', 'nama', 'jenis_kelamin', 'tgl_lahir', 'alamat', 'nama_ortu', 'kelas'],
            ['26001', '0091000001', 'Siti Aminah', 'P', '2010-05-17', 'Jl. Melati', 'Ahmad', 'X RPL 1'],
            ['26002', '0091000002', 'Budi', 'Laki-laki', '', '', '', ''],
            ['26999', '0091000003', 'Duplikat', 'L', '', '', '', ''],
            ['26004', '0091000004', '', 'X', '', '', '', 'Kelas Fiktif'],
        ]);

        $this->actingAs($this->operator())
            ->post(route('operator.import.store', 'siswa'), ['file' => $file])
            ->assertRedirect(route('operator.import.index'));

        $hasil = session('hasil_import');
        $this->assertSame(2, $hasil['berhasil']);
        $this->assertCount(2, $hasil['gagal']);
        $this->assertSame([4, 5], array_column($hasil['gagal'], 'baris'));
        $this->assertStringContainsString('NIS sudah digunakan', implode(' ', $hasil['gagal'][0]['alasan']));
        $this->assertStringContainsString('Kelas Fiktif', implode(' ', $hasil['gagal'][1]['alasan']));

        $siti = Siswa::where('nis', '26001')->firstOrFail();
        $this->assertSame($kelas->id, $siti->kelasPada()?->id);
        $this->assertSame('26001', $siti->user->username);
        $this->assertSame('L', Siswa::where('nis', '26002')->value('jenis_kelamin'));

        $this->get(route('operator.import.index'))->assertOk();
    }

    public function test_import_guru(): void
    {
        $file = $this->xlsx([
            ['nip', 'username', 'nama', 'jenis_kelamin', 'no_hp', 'alamat', 'email'],
            ['198501012010011001', '', 'Budi Santoso', 'L', '0812', 'Jl. A', ''],
            ['', 'honorer01', 'Rina', 'P', '', '', 'rina@contoh.id'],
            ['', '', 'Tanpa Identitas', 'P', '', '', ''],
        ]);

        $this->actingAs($this->operator())->post(route('operator.import.store', 'guru'), ['file' => $file]);

        $hasil = session('hasil_import');
        $this->assertSame(2, $hasil['berhasil']);
        $this->assertCount(1, $hasil['gagal']);
        $this->assertSame('198501012010011001', Guru::where('nama', 'Budi Santoso')->first()->user->username);
        $this->assertDatabaseHas('users', ['username' => 'honorer01', 'email' => 'rina@contoh.id']);
    }

    public function test_file_wajib_excel(): void
    {
        $this->actingAs($this->operator())
            ->post(route('operator.import.store', 'siswa'), ['file' => UploadedFile::fake()->create('data.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('file');
    }
}
