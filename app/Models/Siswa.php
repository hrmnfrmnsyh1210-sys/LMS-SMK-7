<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function orangTua(): BelongsToMany
    {
        return $this->belongsToMany(OrangTua::class, 'orang_tua_siswa')->withTimestamps();
    }

    public function pengumpulanTugas(): HasMany
    {
        return $this->hasMany(PengumpulanTugas::class);
    }

    public function jawabanKuis(): HasMany
    {
        return $this->hasMany(JawabanKuis::class);
    }

    public function presensi(): HasMany
    {
        return $this->hasMany(Presensi::class);
    }

    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class);
    }

    public function suratIzin(): HasMany
    {
        return $this->hasMany(SuratIzin::class);
    }

    /**
     * Pengampu (mapel) di kelas siswa pada tahun ajaran tertentu (default: tahun ajaran aktif).
     *
     * @return Builder<Pengampu>
     */
    public function pengampu(?int $tahunAjaranId = null): Builder
    {
        $tahunAjaranId ??= TahunAjaran::aktifId();

        return Pengampu::query()
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->whereIn('kelas_id', $this->kelasSiswa()->where('tahun_ajaran_id', $tahunAjaranId)->select('kelas_id'));
    }

    /** Siswa terdaftar di kelas pengampu pada tahun ajaran pengampu. */
    public function mengikuti(Pengampu $pengampu): bool
    {
        return $this->kelasSiswa()
            ->where('kelas_id', $pengampu->kelas_id)
            ->where('tahun_ajaran_id', $pengampu->tahun_ajaran_id)
            ->exists();
    }
}
