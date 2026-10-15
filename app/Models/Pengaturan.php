<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $fillable = ['kunci', 'nilai'];

    public const CACHE_KEY = 'pengaturan.semua';

    public const DEFAULT = [
        'nama_sekolah' => 'SMK Negeri 7 Pontianak',
        'alamat_sekolah' => 'Jl. Tanjung Raya 2, Komp. Sekolah Terpadu, Kel. Saigon, Kec. Pontianak Timur, Kota Pontianak, Kalimantan Barat 78232',
        'npsn' => '30107398',
        'telepon_sekolah' => '082157868679',
        'email_sekolah' => 'smkn7ptk@ymail.com',
        'logo' => '',
        'min_panjang_password' => '8',
        'kkm' => '75',

        // Konten website profil sekolah (landing page).
        'web_hero_judul' => 'Sekolah Vokasi Unggul, Siap Kerja dan Berkarakter',
        'web_hero_subjudul' => 'Mencetak lulusan kompeten, kreatif, dan berakhlak mulia yang siap bersaing di dunia industri.',
        'web_nama_singkat' => 'SMKN 7 Pontianak',
        'web_hero_objek' => '',
        'web_video_profil' => '',
        'web_hero_latar' => '',
        'web_tahun_berdiri' => '',
        'web_akreditasi' => '',
        'web_sambutan_nama' => '',
        'web_sambutan_foto' => '',
        'web_sambutan_isi' => '',
        'web_visi' => '',
        'web_misi' => '',
        'web_sejarah' => '',
        'web_ppdb_aktif' => '0',
        'web_ppdb_judul' => 'Penerimaan Peserta Didik Baru',
        'web_ppdb_teks' => '',
        'web_ppdb_tautan' => '',
        'web_maps_embed' => '',
        'web_whatsapp' => '',
        'web_instagram' => '',
        'web_facebook' => '',
        'web_youtube' => '',
        'web_tiktok' => '',
    ];

    /** Memo per request agar tidak membaca cache berulang kali. */
    protected static ?array $memo = null;

    protected static function booted(): void
    {
        static::saved(fn () => static::lupakan());
        static::deleted(fn () => static::lupakan());
    }

    public static function lupakan(): void
    {
        static::$memo = null;
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, string|null> */
    public static function semua(): array
    {
        return static::$memo ??= array_merge(self::DEFAULT, Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->pluck('nilai', 'kunci')->all()
        ));
    }

    public static function ambil(string $kunci, mixed $default = null): mixed
    {
        return static::semua()[$kunci] ?? $default;
    }

    public static function simpan(string $kunci, mixed $nilai): void
    {
        static::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
    }
}
