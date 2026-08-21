<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGudang extends Model
{
    protected $table = 'user_gudang';

    protected $fillable = [
        'user_id',
        'idgudang',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
