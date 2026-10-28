<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\JadwalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jadwal extends Model
{
    /** @use HasFactory<JadwalFactory> */
    use HasFactory;

    protected $table = 'jadwal';

    protected $fillable = ['pengampu_id', 'hari', 'jam_mulai', 'jam_selesai', 'ruang'];

    public const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    /** Nama hari jadwal untuk sebuah tanggal (null untuk hari Minggu). */
    public static function hariDari(CarbonInterface $tanggal): ?string
    {
        return self::HARI[$tanggal->dayOfWeekIso - 1] ?? null;
    }

    public function pengampu(): BelongsTo
    {
        return $this->belongsTo(Pengampu::class);
    }

    public function absensiGuru(): HasMany
    {
        return $this->hasMany(AbsensiGuru::class);
    }

    public function getJamAttribute(): string
    {
        return substr($this->jam_mulai, 0, 5).'–'.substr($this->jam_selesai, 0, 5);
    }
}
