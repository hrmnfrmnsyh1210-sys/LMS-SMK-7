<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    protected $fillable = ['nama_kelas', 'tingkat', 'jurusan_id', 'wali_kelas_id', 'tahun_ajaran_id'];

    public const TINGKAT = ['X', 'XI', 'XII'];

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'wali_kelas_id');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function siswa(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'kelas_siswa')
            ->withPivot('tahun_ajaran_id')
            ->withTimestamps();
    }

    public function pengampu(): HasMany
    {
        return $this->hasMany(Pengampu::class);
    }

    public function scopeTahunAktif(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('tahun_ajaran_id'), TahunAjaran::aktifId());
    }

    public function sedangDipakai(): bool
    {
        return $this->siswa()->exists() || $this->pengampu()->exists();
    }
}
