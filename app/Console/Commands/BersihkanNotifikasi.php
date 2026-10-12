<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

/** Hapus notifikasi lama agar tabel notifications tidak terus membesar di shared hosting. */
#[Signature('lms:bersihkan-notifikasi {--hari=60 : Umur notifikasi yang sudah dibaca (hari)} {--hari-belum-dibaca=180 : Umur notifikasi yang belum dibaca (hari)}')]
#[Description('Hapus notifikasi lama yang sudah dibaca maupun yang terlalu lama tidak dibaca')]
class BersihkanNotifikasi extends Command
{
    public function handle(): int
    {
        $dibaca = DatabaseNotification::whereNotNull('read_at')
            ->where('created_at', '<', now()->subDays((int) $this->option('hari')))
            ->delete();

        $belumDibaca = DatabaseNotification::whereNull('read_at')
            ->where('created_at', '<', now()->subDays((int) $this->option('hari-belum-dibaca')))
            ->delete();

        $this->info("{$dibaca} notifikasi dibaca dan {$belumDibaca} notifikasi lama dihapus.");

        return self::SUCCESS;
    }
}
