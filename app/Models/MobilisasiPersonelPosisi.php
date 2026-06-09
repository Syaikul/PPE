<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobilisasiPersonelPosisi extends Model
{
    protected $table = 'mobilisasi_personel_posisi';

    protected $fillable = [
        'mobilisasi_personel_id',
        'idposisi',
    ];

    public function personel(): BelongsTo
    {
        return $this->belongsTo(MobilisasiPersonel::class, 'mobilisasi_personel_id');
    }
}
