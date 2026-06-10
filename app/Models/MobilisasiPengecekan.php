<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobilisasiPengecekan extends Model
{
    protected $table = 'mobilisasi_pengecekan';

    protected $fillable = [
        'mobilisasi_personel_id',
        'idsubbarang',
        'idbarangvarian',
        'jumlah',
        'status',
        'catatan',
    ];

    public function personel(): BelongsTo
    {
        return $this->belongsTo(MobilisasiPersonel::class, 'mobilisasi_personel_id');
    }
}
