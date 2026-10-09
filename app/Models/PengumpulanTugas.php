<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengumpulanTugas extends Model
{
    use HasFactory;

    protected $table = 'pengumpulan_tugas';

    protected $fillable = ['tugas_id', 'siswa_id', 'file_path', 'jawaban_teks', 'waktu_kumpul', 'nilai', 'feedback'];

    protected function casts(): array
    {
        return ['waktu_kumpul' => 'datetime', 'nilai' => 'decimal:2'];
    }

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(Tugas::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function getTerlambatAttribute(): bool
    {
        return $this->waktu_kumpul->greaterThan($this->tugas->deadline);
    }
}
