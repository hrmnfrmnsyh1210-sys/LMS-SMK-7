<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kepsek extends Model
{
    use HasFactory;

    protected $table = 'kepsek';

    protected $fillable = ['user_id', 'nip', 'nama', 'no_hp', 'jenis_kelamin'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
