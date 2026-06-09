<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobilisasiPerlengkapan extends Model
{
    protected $table = 'mobilisasi_perlengkapan';

    protected $fillable = [
        'mobilisasi_id',
        'idposisi',
        'idsubbarang',
        'qty',
        'jenis',
    ];

    public function mobilisasi(): BelongsTo
    {
        return $this->belongsTo(Mobilisasi::class, 'mobilisasi_id');
    }
}
