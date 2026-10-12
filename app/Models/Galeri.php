<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Foto kegiatan & fasilitas untuk website sekolah. */
class Galeri extends Model
{
    use HasFactory;

    protected $table = 'galeri';

    protected $fillable = ['judul', 'keterangan', 'gambar', 'urutan'];

    public function scopeTerurut(Builder $query): Builder
    {
        return $query->orderBy('urutan')->latest('id');
    }
}
