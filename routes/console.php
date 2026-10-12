<?php

use Illuminate\Support\Facades\Schedule;

/*
| Jadwal tugas otomatis. Di shared hosting cukup satu cron job per menit:
|   * * * * * cd /home/USER/lms && php artisan schedule:run >> /dev/null 2>&1
| (lihat DEPLOYMENT.md)
*/

Schedule::command('lms:tutup-kuis')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('lms:bersihkan-notifikasi')->dailyAt('02:00');
