<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JawabanKuis extends Model
{
    use HasFactory;

    protected $table = 'jawaban_kuis';

    protected $fillable = [
        'kuis_id', 'siswa_id', 'total_skor', 'waktu_mulai', 'waktu_submit', 'urutan_soal', 'jumlah_pindah_tab',
    ];

    protected function casts(): array
    {
        return [
            'waktu_mulai' => 'datetime',
            'waktu_submit' => 'datetime',
            'urutan_soal' => 'array',
            'total_skor' => 'decimal:2',
        ];
    }

    public function kuis(): BelongsTo
    {
        return $this->belongsTo(Kuis::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function detail(): HasMany
    {
        return $this->hasMany(JawabanKuisDetail::class);
    }

    public function sudahSubmit(): bool
    {
        return $this->waktu_submit !== null;
    }

    /** Sudah submit tetapi masih ada esai yang belum dikoreksi. */
    public function menungguKoreksi(): bool
    {
        return $this->sudahSubmit() && $this->total_skor === null;
    }
}
