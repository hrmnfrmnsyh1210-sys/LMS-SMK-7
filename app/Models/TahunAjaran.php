<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class TahunAjaran extends Model
{
    use HasFactory;

    protected $table = 'tahun_ajaran';

    protected $fillable = ['nama', 'semester', 'status_aktif'];

    protected static ?self $aktifCache = null;

    protected static bool $aktifDimuat = false;

    protected function casts(): array
    {
        return ['status_aktif' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::lupakanAktif());
        static::deleted(fn () => static::lupakanAktif());
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    public function pengampu(): HasMany
    {
        return $this->hasMany(Pengampu::class);
    }

    public function kelasSiswa(): HasMany
    {
        return $this->hasMany(KelasSiswa::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->nama.' — '.ucfirst($this->semester);
    }

    /** Tahun ajaran aktif (di-cache per request). */
    public static function aktif(): ?self
    {
        if (! static::$aktifDimuat) {
            static::$aktifCache = static::where('status_aktif', true)->first();
            static::$aktifDimuat = true;
        }

        return static::$aktifCache;
    }

    public static function aktifId(): ?int
    {
        return static::aktif()?->id;
    }

    public static function lupakanAktif(): void
    {
        static::$aktifCache = null;
        static::$aktifDimuat = false;
    }

    /** Jadikan tahun ajaran ini satu-satunya yang aktif. */
    public function aktifkan(): void
    {
        DB::transaction(function () {
            static::where('id', '!=', $this->id)->update(['status_aktif' => false]);
            $this->update(['status_aktif' => true]);
        });
    }

    public function sedangDipakai(): bool
    {
        return $this->kelas()->exists() || $this->pengampu()->exists() || $this->kelasSiswa()->exists();
    }
}
