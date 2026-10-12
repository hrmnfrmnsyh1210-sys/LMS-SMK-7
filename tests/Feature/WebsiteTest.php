<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\Galeri;
use App\Models\Jurusan;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Website profil sekolah yang dapat dibuka publik tanpa login. */
class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_beranda_dapat_dibuka_tamu_walau_belum_ada_konten(): void
    {
        $this->get(route('beranda'))
            ->assertOk()
            ->assertSee(Pengaturan::DEFAULT['web_hero_judul'])
            ->assertSee(route('login'));
    }

    public function test_beranda_menampilkan_konten_dari_operator_dan_data_sekolah(): void
    {
        Pengaturan::simpan('web_visi', 'Menjadi sekolah vokasi teladan');
        Pengaturan::simpan('web_misi', "Pembelajaran berbasis industri\nPembentukan karakter");
        Pengaturan::simpan('web_ppdb_aktif', '1');
        Pengaturan::simpan('web_ppdb_judul', 'PPDB 2027 Dibuka');
        Jurusan::factory()->create(['nama' => 'Desain Komunikasi Visual', 'deskripsi' => 'Belajar desain grafis.']);
        Siswa::factory()->count(3)->create();
        Siswa::factory()->create(['status' => 'lulus']);

        $this->get(route('beranda'))
            ->assertOk()
            ->assertSee('Menjadi sekolah vokasi teladan')
            ->assertSeeInOrder(['Pembelajaran berbasis industri', 'Pembentukan karakter'])
            ->assertSee('PPDB 2027 Dibuka')
            ->assertSee('Desain Komunikasi Visual')
            ->assertSee('Belajar desain grafis.')
            ->assertViewHas('statistik', fn ($s) => $s['siswa'] === 3);
    }

    public function test_hero_memakai_foto_latar_pilihan_operator(): void
    {
        $this->get(route('beranda'))->assertDontSee('website/latar.jpg');

        Pengaturan::simpan('web_hero_latar', 'website/latar.jpg');

        $this->get(route('beranda'))->assertOk()->assertSee(file_url('website/latar.jpg'));
    }

    public function test_hero_menampilkan_nama_singkat_dan_tombol_video_youtube(): void
    {
        $this->get(route('beranda'))->assertDontSee('youtube-nocookie.com', false);

        Pengaturan::simpan('web_nama_singkat', 'SMKN 7');
        Pengaturan::simpan('web_video_profil', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10');

        $this->get(route('beranda'))
            ->assertOk()
            ->assertSee('SMKN 7')
            ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
    }

    public function test_banner_ppdb_disembunyikan_saat_nonaktif(): void
    {
        Pengaturan::simpan('web_ppdb_aktif', '0');
        Pengaturan::simpan('web_ppdb_judul', 'PPDB 2027 Dibuka');

        $this->get(route('beranda'))->assertOk()->assertDontSee('PPDB 2027 Dibuka');
    }

    public function test_hanya_berita_terbit_yang_tampil(): void
    {
        $terbit = Berita::factory()->create(['judul' => 'Juara Lomba Kompetensi Siswa']);
        $draf = Berita::factory()->draf()->create(['judul' => 'Berita Masih Draf']);
        $terjadwal = Berita::factory()->create(['judul' => 'Berita Terjadwal', 'diterbitkan_pada' => now()->addDay()]);

        $this->get(route('web.berita.index'))
            ->assertOk()
            ->assertSee('Juara Lomba Kompetensi Siswa')
            ->assertDontSee('Berita Masih Draf')
            ->assertDontSee('Berita Terjadwal');

        $this->get(route('beranda'))->assertSee('Juara Lomba Kompetensi Siswa')->assertDontSee('Berita Masih Draf');

        $this->get(route('web.berita.show', $terbit))->assertOk()->assertSee('Juara Lomba Kompetensi Siswa');
        $this->get(route('web.berita.show', $draf))->assertNotFound();
        $this->get(route('web.berita.show', $terjadwal))->assertNotFound();
    }

    public function test_daftar_berita_dapat_disaring_per_kategori(): void
    {
        Berita::factory()->create(['judul' => 'Siswa Raih Medali Emas', 'kategori' => 'prestasi']);
        Berita::factory()->create(['judul' => 'Rapat Komite Sekolah', 'kategori' => 'agenda']);

        $this->get(route('web.berita.index', ['kategori' => 'prestasi']))
            ->assertOk()
            ->assertSee('Siswa Raih Medali Emas')
            ->assertDontSee('Rapat Komite Sekolah');
    }

    public function test_isi_berita_markdown_dirender_tanpa_html_berbahaya(): void
    {
        $berita = Berita::factory()->create([
            'judul' => 'Judul <b>tebal</b>',
            'isi' => "Ini **penting**.\n\n<script>alert('xss')</script>\n\n[klik](javascript:alert(1))",
        ]);

        $this->get(route('web.berita.show', $berita))
            ->assertOk()
            ->assertSee('<strong>penting</strong>', false)
            ->assertSee('Judul &lt;b&gt;tebal&lt;/b&gt;', false)
            ->assertDontSee("<script>alert('xss')</script>", false)
            ->assertDontSee('javascript:alert', false);
    }

    public function test_halaman_galeri_menampilkan_foto(): void
    {
        Galeri::factory()->create(['judul' => 'Praktik Laboratorium Jaringan']);

        $this->get(route('web.galeri'))->assertOk()->assertSee('Praktik Laboratorium Jaringan');
    }
}
