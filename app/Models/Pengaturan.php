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
        'nama_sekolah' => 'SMK Negeri 7',
        'alamat_sekolah' => '',
        'npsn' => '',
        'telepon_sekolah' => '',
        'email_sekolah' => '',
        'logo' => '',
        'min_panjang_password' => '8',
        'kkm' => '75',
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
