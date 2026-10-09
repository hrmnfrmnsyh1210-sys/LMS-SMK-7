<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratIzin extends Model
{
    use HasFactory;

    protected $table = 'surat_izin';

    protected $fillable = [
        'siswa_id', 'kelas_id', 'pengaju_user_id', 'tanggal_mulai', 'tanggal_selesai', 'jenis', 'keterangan', 'file_path',
        'status', 'diproses_oleh', 'catatan',
    ];

    protected function casts(): array
    {
        return ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date'];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /** User yang mengajukan: siswa sendiri atau orang tuanya. */
    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengaju_user_id');
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'diproses_oleh');
    }
}
