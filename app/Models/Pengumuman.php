<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengumuman extends Model
{
    protected $table = 'pengumuman';

    public const TARGET = ['semua' => 'Semua warga sekolah', 'guru' => 'Guru', 'siswa' => 'Siswa', 'ortu' => 'Orang tua', 'kelas' => 'Kelas'];

    protected $fillable = ['pembuat_user_id', 'judul', 'isi', 'target', 'kelas_id'];

    /** Pengumuman yang terlihat oleh siswa: untuk semua, untuk siswa, atau untuk kelasnya. */
    public function scopeUntukSiswa(Builder $query, ?int $kelasId): Builder
    {
        return $query->where(fn ($q) => $q->whereIn('target', ['semua', 'siswa'])
            ->when($kelasId, fn ($q) => $q->orWhere(fn ($q) => $q->where('target', 'kelas')->where('kelas_id', $kelasId))));
    }

    /**
     * Pengumuman yang terlihat oleh orang tua: untuk semua, untuk orang tua, atau untuk kelas anaknya.
     *
     * @param  list<int>  $kelasIds
     */
    public function scopeUntukOrtu(Builder $query, array $kelasIds): Builder
    {
        return $query->where(fn ($q) => $q->whereIn('target', ['semua', 'ortu'])
            ->orWhere(fn ($q) => $q->where('target', 'kelas')->whereIn('kelas_id', $kelasIds)));
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembuat_user_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }
}
