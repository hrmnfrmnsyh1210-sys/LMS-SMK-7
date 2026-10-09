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
}
