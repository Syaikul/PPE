<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonelPosisi extends Model
{
    protected $table = 'personel_posisi';

    protected $fillable = [
        'personel_id',
        'idposisi',
    ];

    public function personel(): BelongsTo
    {
        return $this->belongsTo(Personel::class, 'personel_id');
    }
}
