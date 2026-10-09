<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Soal extends Model
{
    use HasFactory;

    protected $table = 'soal';

    public const OPSI = ['a', 'b', 'c', 'd'];

    protected $fillable = [
        'kuis_id', 'tipe', 'pertanyaan', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'kunci_jawaban', 'bobot',
    ];

    public function kuis(): BelongsTo
    {
        return $this->belongsTo(Kuis::class);
    }

    public function isPilihanGanda(): bool
    {
        return $this->tipe === 'pg';
    }
}
