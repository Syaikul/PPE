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
        'mobilisasi_personel_id',
        'idsubbarang',
        'qty',
        'jenis',
        'untuk_user',
    ];

    protected $casts = [
        'untuk_user' => 'boolean',
    ];

    public function mobilisasi(): BelongsTo
    {
        return $this->belongsTo(Mobilisasi::class, 'mobilisasi_id');
    }

    public function mobilisasiPersonel(): BelongsTo
    {
        return $this->belongsTo(MobilisasiPersonel::class, 'mobilisasi_personel_id');
    }
}
