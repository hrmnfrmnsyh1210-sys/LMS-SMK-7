<?php

namespace App\Models;

use App\Models\Concerns\RelasiAnakTunggal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tugas extends Model
{
    use HasFactory, RelasiAnakTunggal;

    protected $table = 'tugas';

    /** Status tugas dari sisi siswa (lihat statusSiswa()). */
    public const STATUS_SISWA = ['belum' => 'Belum dikumpulkan', 'dikumpulkan' => 'Dikumpulkan', 'dinilai' => 'Dinilai', 'terlewat' => 'Terlewat'];

    protected $fillable = ['pengampu_id', 'judul', 'deskripsi', 'file_path', 'deadline', 'nilai_maks'];

    protected function casts(): array
    {
        return ['deadline' => 'datetime'];
    }

    public function pengampu(): BelongsTo
    {
        return $this->belongsTo(Pengampu::class);
    }

    public function pengumpulan(): HasMany
    {
        return $this->hasMany(PengumpulanTugas::class);
    }

    public function sudahLewatDeadline(): bool
    {
        return now()->gt($this->deadline);
    }

    /** Status tugas dari sisi siswa: 'dinilai' | 'dikumpulkan' | 'terlewat' | 'belum'. */
    public function statusSiswa(?PengumpulanTugas $pengumpulan): string
    {
        return match (true) {
            $pengumpulan?->nilai !== null => 'dinilai',
            $pengumpulan !== null => 'dikumpulkan',
            $this->sudahLewatDeadline() => 'terlewat',
            default => 'belum',
        };
    }
}
