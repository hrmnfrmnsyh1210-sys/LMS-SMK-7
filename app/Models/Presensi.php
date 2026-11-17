<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presensi extends Model
{
    protected $table = 'presensi';

    protected $fillable = [
        'pengampu_id',
        'siswa_id',
        'tanggal',
        'pertemuan_ke',
        'status',
        'catatan',
        'materi',
        'tugas',
        'dokumentasi',
        'diabsen_oleh',
        'waktu_absen',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'waktu_absen' => 'datetime',
        ];
    }

    public function diabsenOlehSiswa(): bool
    {
        return $this->diabsen_oleh === 'siswa';
    }

    public function pengampu(): BelongsTo
    {
        return $this->belongsTo(Pengampu::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
