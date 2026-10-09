<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengumuman extends Model
{
    protected $table = 'pengumuman';

    protected $fillable = ['pembuat_user_id', 'judul', 'isi', 'target', 'kelas_id'];

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembuat_user_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }
}
