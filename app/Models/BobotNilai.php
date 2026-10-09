<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BobotNilai extends Model
{
    protected $table = 'bobot_nilai';

    protected $fillable = ['pengampu_id', 'bobot_tugas', 'bobot_kuis', 'bobot_uts', 'bobot_uas'];

    public const DEFAULT = ['bobot_tugas' => 20, 'bobot_kuis' => 20, 'bobot_uts' => 30, 'bobot_uas' => 30];

    public function pengampu(): BelongsTo
    {
        return $this->belongsTo(Pengampu::class);
    }
}
