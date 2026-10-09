<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JawabanKuisDetail extends Model
{
    protected $table = 'jawaban_kuis_detail';

    protected $fillable = ['jawaban_kuis_id', 'soal_id', 'jawaban', 'skor'];

    protected function casts(): array
    {
        return ['skor' => 'decimal:2'];
    }

    public function jawabanKuis(): BelongsTo
    {
        return $this->belongsTo(JawabanKuis::class);
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
