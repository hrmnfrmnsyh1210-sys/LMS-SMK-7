<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Materi extends Model
{
    use HasFactory;

    protected $table = 'materi';

    protected $fillable = ['pengampu_id', 'judul', 'deskripsi', 'file_path', 'tautan', 'pertemuan_ke'];

    public function pengampu(): BelongsTo
    {
        return $this->belongsTo(Pengampu::class);
    }
}
