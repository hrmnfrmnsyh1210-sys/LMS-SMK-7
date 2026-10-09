<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Notifikasi sederhana di dashboard (database). */
class Pemberitahuan extends Notification
{
    public function __construct(public string $pesan, public ?string $url = null) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array{pesan: string, url: ?string} */
    public function toArray(object $notifiable): array
    {
        return ['pesan' => $this->pesan, 'url' => $this->url];
    }
}
