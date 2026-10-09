<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumBalasan extends Model
{
    protected $table = 'forum_balasan';

    protected $fillable = ['topik_id', 'user_id', 'isi'];

    public function topik(): BelongsTo
    {
        return $this->belongsTo(ForumTopik::class, 'topik_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
