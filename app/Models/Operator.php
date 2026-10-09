<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Operator extends Model
{
    protected $table = 'operator';

    protected $fillable = ['user_id', 'nama', 'no_hp'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
