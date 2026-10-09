<?php

namespace App\Models;

use App\Models\Concerns\RelasiAnakTunggal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumTopik extends Model
{
    use RelasiAnakTunggal;

    protected $table = 'forum_topik';

    protected $fillable = ['pengampu_id', 'user_id', 'judul', 'isi'];

    public function pengampu(): BelongsTo
    {
        return $this->belongsTo(Pengampu::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function balasan(): HasMany
    {
        return $this->hasMany(ForumBalasan::class, 'topik_id');
    }
}
