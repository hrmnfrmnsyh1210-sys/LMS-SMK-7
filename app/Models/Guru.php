<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guru extends Model
{
    use HasFactory;

    protected $table = 'guru';

    protected $fillable = ['user_id', 'nip', 'nama', 'jenis_kelamin', 'no_hp', 'alamat'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pengampu(): HasMany
    {
        return $this->hasMany(Pengampu::class);
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiGuru::class);
    }

    public function kelasWali(): HasMany
    {
        return $this->hasMany(Kelas::class, 'wali_kelas_id');
    }

    /** Kelas yang diwalikan pada tahun ajaran aktif (null bila bukan wali kelas). */
    public function kelasWaliAktif(): ?Kelas
    {
        return $this->kelasWali()->tahunAktif()->first();
    }
}
