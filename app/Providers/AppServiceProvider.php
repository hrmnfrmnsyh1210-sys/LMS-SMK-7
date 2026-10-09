<?php

namespace App\Providers;

use App\Models\Pengaturan;
use App\Models\TahunAjaran;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        // Kebijakan password mengikuti pengaturan sistem.
        Password::defaults(fn () => Password::min((int) Pengaturan::ambil('min_panjang_password', 8)));

        View::composer('*', function ($view) {
            try {
                $view->with(['sekolah' => Pengaturan::semua(), 'tahunAktif' => TahunAjaran::aktif()]);
            } catch (\Throwable) {
                // Database belum siap (mis. sebelum migrasi): pakai nilai bawaan.
                $view->with(['sekolah' => Pengaturan::DEFAULT, 'tahunAktif' => null]);
            }
        });
    }
}
