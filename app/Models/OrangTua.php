<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrangTua extends Model
{
    use HasFactory;

    protected $table = 'orang_tua';

    public const HUBUNGAN = ['ayah' => 'Ayah', 'ibu' => 'Ibu', 'wali' => 'Wali'];

    protected $fillable = ['user_id', 'nama', 'hubungan', 'no_hp', 'alamat'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Anak (siswa) yang dipantau. */
    public function anak(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'orang_tua_siswa')->withTimestamps()->orderBy('nama');
    }

    public function getLabelHubunganAttribute(): string
    {
        return self::HUBUNGAN[$this->hubungan] ?? $this->hubungan;
    }

    public function memantau(Siswa $siswa): bool
    {
        return $this->anak()->whereKey($siswa->id)->exists();
    }
}
