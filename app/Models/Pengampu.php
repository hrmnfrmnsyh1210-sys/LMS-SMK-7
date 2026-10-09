<?php

namespace App\Models;

use App\Models\Concerns\RelasiAnakTunggal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pengampu extends Model
{
    use HasFactory, RelasiAnakTunggal;

    protected $table = 'pengampu';

    /** @var array<string, string> parameter route anak => nama relasi */
    protected array $relasiAnakRoute = ['topik' => 'forumTopik'];

    protected $fillable = ['guru_id', 'mapel_id', 'kelas_id', 'tahun_ajaran_id'];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function jadwal(): HasMany
    {
        return $this->hasMany(Jadwal::class);
    }

    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class);
    }

    public function tugas(): HasMany
    {
        return $this->hasMany(Tugas::class);
    }

    public function kuis(): HasMany
    {
        return $this->hasMany(Kuis::class);
    }

    public function presensi(): HasMany
    {
        return $this->hasMany(Presensi::class);
    }

    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class);
    }

    public function bobotNilai(): HasOne
    {
        return $this->hasOne(BobotNilai::class);
    }

    public function forumTopik(): HasMany
    {
        return $this->hasMany(ForumTopik::class);
    }

    public function scopeTahunAktif(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('tahun_ajaran_id'), TahunAjaran::aktifId());
    }

    /** Pengampu yang sudah punya data pembelajaran tidak boleh dihapus. */
    public function sedangDipakai(): bool
    {
        return $this->materi()->exists() || $this->tugas()->exists() || $this->kuis()->exists()
            || $this->presensi()->exists() || $this->nilai()->exists();
    }

    /**
     * Siswa aktif di kelas pengampu ini pada tahun ajaran pengampu.
     *
     * @return Collection<int, Siswa>
     */
    public function daftarSiswa(): Collection
    {
        return Siswa::query()
            ->where('status', 'aktif')
            ->whereHas('kelasSiswa', fn ($q) => $q
                ->where('kelas_id', $this->kelas_id)
                ->where('tahun_ajaran_id', $this->tahun_ajaran_id))
            ->orderBy('nama')
            ->get();
    }

    /** Bobot nilai pengampu; dibuat dengan nilai default 20/20/30/30 bila belum ada. */
    public function bobot(): BobotNilai
    {
        return $this->bobotNilai()->firstOrCreate([], BobotNilai::DEFAULT);
    }

    /** Label singkat, mis. "Matematika — X RPL 1". */
    public function getLabelAttribute(): string
    {
        return $this->mapel->nama_mapel.' — '.$this->kelas->nama_kelas;
    }
}
