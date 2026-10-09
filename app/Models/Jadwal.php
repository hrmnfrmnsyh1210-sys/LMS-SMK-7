<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jadwal extends Model
{
    protected $table = 'jadwal';

    protected $fillable = ['pengampu_id', 'hari', 'jam_mulai', 'jam_selesai', 'ruang'];

    public const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    public function pengampu(): BelongsTo
    {
        return $this->belongsTo(Pengampu::class);
    }

    public function getJamAttribute(): string
    {
        return substr($this->jam_mulai, 0, 5).'–'.substr($this->jam_selesai, 0, 5);
    }
}
