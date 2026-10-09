<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswa';

    protected $fillable = [
        'user_id', 'nis', 'nisn', 'nama', 'jenis_kelamin', 'tgl_lahir', 'alamat', 'nama_ortu', 'status',
    ];

    protected function casts(): array
    {
        return ['tgl_lahir' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kelasSiswa(): HasMany
    {
        return $this->hasMany(KelasSiswa::class);
    }

    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kelas_siswa')
            ->withPivot('tahun_ajaran_id')
            ->withTimestamps();
    }

    /** Kelas siswa pada tahun ajaran tertentu (default: tahun ajaran aktif). */
    public function kelasPada(?int $tahunAjaranId = null): ?Kelas
    {
        $tahunAjaranId ??= TahunAjaran::aktifId();

        return $this->kelas()->wherePivot('tahun_ajaran_id', $tahunAjaranId)->first();
    }
}
