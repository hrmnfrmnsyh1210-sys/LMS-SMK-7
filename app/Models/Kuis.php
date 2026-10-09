<?php

namespace App\Models;

use App\Models\Concerns\RelasiAnakTunggal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kuis extends Model
{
    use HasFactory, RelasiAnakTunggal;

    protected $table = 'kuis';

    public const JENIS = ['kuis' => 'Kuis', 'uts' => 'UTS', 'uas' => 'UAS'];

    /** Status kuis dari sisi siswa, urut prioritas tampil (lihat PengerjaanKuisService::statusSiswa). */
    public const STATUS_SISWA = [
        'tersedia' => 'Bisa dikerjakan',
        'dikerjakan' => 'Sedang dikerjakan',
        'akan_datang' => 'Akan datang',
        'dikumpulkan' => 'Sudah dikerjakan',
        'terlewat' => 'Terlewat',
    ];

    protected $fillable = [
        'pengampu_id', 'judul', 'jenis', 'waktu_mulai', 'waktu_selesai', 'durasi_menit', 'acak_soal', 'tampilkan_hasil',
    ];

    protected function casts(): array
    {
        return [
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
            'acak_soal' => 'boolean',
            'tampilkan_hasil' => 'boolean',
        ];
    }

    public function pengampu(): BelongsTo
    {
        return $this->belongsTo(Pengampu::class);
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class);
    }

    public function jawaban(): HasMany
    {
        return $this->hasMany(JawabanKuis::class);
    }

    public function getLabelJenisAttribute(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }

    /** 'akan_datang' | 'berlangsung' | 'selesai' */
    public function getStatusWaktuAttribute(): string
    {
        return match (true) {
            now()->lt($this->waktu_mulai) => 'akan_datang',
            now()->gt($this->waktu_selesai) => 'selesai',
            default => 'berlangsung',
        };
    }

    /** Soal tidak boleh diubah lagi setelah ada siswa yang mulai mengerjakan. */
    public function terkunci(): bool
    {
        return $this->jawaban()->exists();
    }
}
