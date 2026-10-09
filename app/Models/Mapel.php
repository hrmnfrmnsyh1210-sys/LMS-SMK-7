<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mapel extends Model
{
    use HasFactory;

    protected $table = 'mapel';

    protected $fillable = ['kode', 'nama_mapel'];

    public function pengampu(): HasMany
    {
        return $this->hasMany(Pengampu::class);
    }
}
