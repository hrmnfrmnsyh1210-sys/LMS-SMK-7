<?php

namespace Tests\Feature\Operator;

use App\Models\Berita;
use App\Models\Galeri;
use App\Models\Pengaturan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** CMS website sekolah yang dikelola operator. */
class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs($this->operator());
    }

    /** @return array<string, mixed> */
    private function kontenValid(array $ubah = []): array
    {
        return [
            'web_hero_judul' => 'Sekolah Pencetak Juara',
            'web_visi' => 'Unggul dan berkarakter',
            ...$ubah,
        ];
    }

    public function test_operator_menyimpan_konten_dan_mengganti_gambar(): void
    {
        Storage::disk('public')->put('website/lama.jpg', 'x');
        Pengaturan::simpan('web_hero_latar', 'website/lama.jpg');

        $this->put(route('operator.website.update'), $this->kontenValid([
            'web_hero_latar' => UploadedFile::fake()->image('latar.jpg', 1600, 900),
            'web_hero_objek' => UploadedFile::fake()->image('gedung.png'),
            'web_nama_singkat' => 'SMKN 7',
            'web_ppdb_aktif' => '1',
        ]))->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame('Sekolah Pencetak Juara', Pengaturan::ambil('web_hero_judul'));
        $this->assertSame('Unggul dan berkarakter', Pengaturan::ambil('web_visi'));
        $this->assertSame('SMKN 7', Pengaturan::ambil('web_nama_singkat'));
        $this->assertSame('1', Pengaturan::ambil('web_ppdb_aktif'));
        Storage::disk('public')->assertMissing('website/lama.jpg');
        Storage::disk('public')->assertExists(Pengaturan::ambil('web_hero_latar'));
        Storage::disk('public')->assertExists(Pengaturan::ambil('web_hero_objek'));
    }

    public function test_foto_objek_harus_png_atau_webp_agar_bisa_transparan(): void
    {
        $this->put(route('operator.website.update'), $this->kontenValid(['web_hero_objek' => UploadedFile::fake()->image('gedung.jpg')]))
            ->assertSessionHasErrors(['web_hero_objek' => 'Foto objek harus berupa file bertipe: png, webp.']);
    }

    public function test_video_profil_hanya_menerima_tautan_youtube(): void
    {
        $this->put(route('operator.website.update'), $this->kontenValid(['web_video_profil' => 'https://vimeo.com/123456']))
            ->assertSessionHasErrors(['web_video_profil' => 'Tautan video harus berupa tautan YouTube.']);

        $this->put(route('operator.website.update'), $this->kontenValid(['web_video_profil' => 'https://youtu.be/dQw4w9WgXcQ']))
            ->assertSessionHasNoErrors();
        $this->assertSame('https://youtu.be/dQw4w9WgXcQ', Pengaturan::ambil('web_video_profil'));
    }

    public function test_ppdb_nonaktif_bila_kotak_tidak_dicentang_dan_gambar_dapat_dihapus(): void
    {
        Pengaturan::simpan('web_ppdb_aktif', '1');
        Storage::disk('public')->put('website/kepsek.jpg', 'x');
        Pengaturan::simpan('web_sambutan_foto', 'website/kepsek.jpg');

        $this->put(route('operator.website.update'), $this->kontenValid(['hapus_gambar' => ['web_sambutan_foto']]))
            ->assertSessionHasNoErrors();

        $this->assertSame('0', Pengaturan::ambil('web_ppdb_aktif'));
        $this->assertSame('', Pengaturan::ambil('web_sambutan_foto'));
        Storage::disk('public')->assertMissing('website/kepsek.jpg');
    }

    public function test_kode_iframe_google_maps_disimpan_sebagai_tautan_src(): void
    {
        $iframe = '<iframe src="https://www.google.com/maps/embed?pb=!1m18&amp;z=1" width="600" height="450"></iframe>';

        $this->put(route('operator.website.update'), $this->kontenValid(['web_maps_embed' => $iframe]))
            ->assertSessionHasNoErrors();

        $this->assertSame('https://www.google.com/maps/embed?pb=!1m18&z=1', Pengaturan::ambil('web_maps_embed'));
    }

    public function test_ganti_foto_tanpa_menyentuh_peta_yang_sudah_tersimpan(): void
    {
        $peta = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15959.27!2d109.33!3d-0.03!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e1d584b4fff467b%3A0x15c3a55c77e7d2f2!2sBenua%20Melayu%20Laut%2C%20Pontianak!5e0!3m2!1sen!2sid!4v1791793654515!5m2!1sen!2sid';
        Pengaturan::simpan('web_maps_embed', $peta);

        $halaman = $this->get(route('operator.website.edit'))->assertOk();
        preg_match('~<textarea name="web_maps_embed"[^>]*>(.*?)</textarea>~s', $halaman->getContent(), $isian);

        $this->put(route('operator.website.update'), $this->kontenValid([
            'web_maps_embed' => html_entity_decode($isian[1]),
            'web_hero_latar' => UploadedFile::fake()->image('latar.jpeg', 1600, 900),
        ]))->assertSessionHasNoErrors();

        $this->assertSame($peta, Pengaturan::ambil('web_maps_embed'));
    }

    public function test_tautan_peta_selain_google_maps_ditolak(): void
    {
        $this->put(route('operator.website.update'), $this->kontenValid(['web_maps_embed' => 'https://situs-jahat.test/embed']))
            ->assertSessionHasErrors(['web_maps_embed' => 'Tautan peta harus berupa tautan sematan Google Maps (Bagikan → Sematkan peta).']);
    }

    public function test_judul_utama_wajib_diisi(): void
    {
        $this->put(route('operator.website.update'), ['web_hero_judul' => ''])
            ->assertSessionHasErrors(['web_hero_judul' => 'Judul utama wajib diisi.']);
    }

    public function test_berita_disimpan_sebagai_draf_atau_langsung_terbit_dengan_slug_unik(): void
    {
        $this->post(route('operator.berita.store'), [
            'kategori' => 'prestasi', 'judul' => 'Juara Lomba LKS', 'isi' => 'Isi berita.', 'terbitkan' => '0',
        ])->assertRedirect(route('operator.berita.index'));

        $this->post(route('operator.berita.store'), [
            'kategori' => 'berita', 'judul' => 'Juara Lomba LKS', 'isi' => 'Isi kedua.', 'terbitkan' => '1',
            'gambar' => UploadedFile::fake()->image('sampul.jpg'),
        ])->assertSessionHasNoErrors();

        [$draf, $terbit] = Berita::orderBy('id')->get();
        $this->assertNull($draf->diterbitkan_pada);
        $this->assertSame('juara-lomba-lks', $draf->slug);
        $this->assertTrue($terbit->sudahTerbit());
        $this->assertSame('juara-lomba-lks-2', $terbit->slug);
        Storage::disk('public')->assertExists($terbit->gambar);
    }

    public function test_kategori_berita_tidak_dikenal_ditolak(): void
    {
        $this->post(route('operator.berita.store'), ['kategori' => 'gosip', 'judul' => 'X', 'isi' => 'Y'])
            ->assertSessionHasErrors('kategori');

        $this->assertDatabaseCount('berita', 0);
    }

    public function test_ubah_berita_mempertahankan_slug_dan_tanggal_terbit(): void
    {
        $berita = Berita::factory()->create(['judul' => 'Judul Awal', 'diterbitkan_pada' => '2026-09-01 08:00:00']);

        $this->put(route('operator.berita.update', $berita), [
            'kategori' => 'agenda', 'judul' => 'Judul Baru', 'isi' => 'Isi baru.', 'terbitkan' => '1',
        ])->assertSessionHasNoErrors();

        $berita->refresh();
        $this->assertSame('Judul Baru', $berita->judul);
        $this->assertSame('judul-awal', $berita->slug);
        $this->assertSame('2026-09-01 08:00:00', $berita->diterbitkan_pada->toDateTimeString());
    }

    public function test_hapus_berita_menghapus_gambarnya(): void
    {
        Storage::disk('public')->put('berita/sampul.jpg', 'x');
        $berita = Berita::factory()->create(['gambar' => 'berita/sampul.jpg']);

        $this->delete(route('operator.berita.destroy', $berita))->assertSessionHas('success');

        $this->assertModelMissing($berita);
        Storage::disk('public')->assertMissing('berita/sampul.jpg');
    }

    public function test_tambah_foto_galeri_wajib_menyertakan_foto(): void
    {
        $this->post(route('operator.galeri.store'), ['judul' => 'Upacara'])
            ->assertSessionHasErrors(['gambar' => 'Foto wajib diisi.']);

        $this->post(route('operator.galeri.store'), ['judul' => 'Upacara', 'gambar' => UploadedFile::fake()->image('a.jpg'), 'urutan' => 2])
            ->assertRedirect(route('operator.galeri.index'));

        $galeri = Galeri::sole();
        $this->assertSame(2, $galeri->urutan);
        Storage::disk('public')->assertExists($galeri->gambar);
    }

    public function test_ubah_tanpa_foto_baru_mempertahankan_foto_dan_hapus_membuang_file(): void
    {
        Storage::disk('public')->put('galeri/a.jpg', 'x');
        $galeri = Galeri::factory()->create(['gambar' => 'galeri/a.jpg']);

        $this->put(route('operator.galeri.update', $galeri), ['judul' => 'Judul Baru'])->assertSessionHasNoErrors();
        $this->assertSame('galeri/a.jpg', $galeri->fresh()->gambar);

        $this->delete(route('operator.galeri.destroy', $galeri));
        $this->assertModelMissing($galeri);
        Storage::disk('public')->assertMissing('galeri/a.jpg');
    }

    public function test_selain_operator_tidak_dapat_mengelola_website(): void
    {
        $this->actingAs($this->userRole('guru'));

        $this->get(route('operator.website.edit'))->assertForbidden();
        $this->post(route('operator.berita.store'), ['kategori' => 'berita', 'judul' => 'X', 'isi' => 'Y'])->assertForbidden();
        $this->assertDatabaseCount('berita', 0);
    }
}
