<?php

use App\Models\Pengaturan;
use Illuminate\Support\Carbon;

if (! function_exists('pengaturan')) {
    function pengaturan(string $kunci, mixed $default = null): mixed
    {
        return Pengaturan::ambil($kunci, $default);
    }
}

if (! function_exists('tanggal_indo')) {
    /** Contoh: "Kamis, 8 Oktober 2026" atau "8 Oktober 2026 14:30". */
    function tanggal_indo(mixed $tanggal, bool $denganHari = true, bool $denganJam = false): string
    {
        if (blank($tanggal)) {
            return '-';
        }

        $carbon = $tanggal instanceof DateTimeInterface
            ? Carbon::instance($tanggal)
            : Carbon::parse($tanggal);

        $format = ($denganHari ? 'l, ' : '').'j F Y'.($denganJam ? ' H:i' : '');

        return $carbon->locale('id')->translatedFormat($format);
    }
}

if (! function_exists('file_url')) {
    /**
     * URL file di disk public. Jika symlink public/storage tidak ada
     * (shared hosting tanpa storage:link), pakai route fallback.
     */
    function file_url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (is_dir(public_path('storage'))) {
            return asset('storage/'.$path);
        }

        return route('berkas', ['path' => $path]);
    }
}
