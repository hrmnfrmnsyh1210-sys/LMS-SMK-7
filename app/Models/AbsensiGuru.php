<?php

namespace App\Models;

use Database\Factories\AbsensiGuruFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiGuru extends Model
{
    /** @use HasFactory<AbsensiGuruFactory> */
    use HasFactory;

    protected $table = 'absensi_guru';

    protected $fillable = ['guru_id', 'jadwal_id', 'tanggal', 'jam_masuk', 'jam_keluar', 'status', 'ip_masuk', 'ip_keluar'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class);
    }

    /** Lama berada di kelas dalam format "1j 30m" (null bila belum absen keluar). */
    public function durasi(): ?string
    {
        if (! $this->jam_keluar) {
            return null;
        }

        $menit = intdiv(strtotime($this->jam_keluar) - strtotime($this->jam_masuk), 60);

        return intdiv($menit, 60).'j '.($menit % 60).'m';
    }
}
