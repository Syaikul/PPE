<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpeKeluar extends Model
{
    protected $table = 'ppe_keluar';

    protected $fillable = [
        'idgudang',
        'idpersonel',
        'idsubbarang',
        'idbarangvarian',
        'qty',
        'tanggal',
        'catatan',
        'personel_id',
        'mobilisasi_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function personel(): BelongsTo
    {
        return $this->belongsTo(Personel::class, 'personel_id');
    }

    public function mobilisasi(): BelongsTo
    {
        return $this->belongsTo(Mobilisasi::class, 'mobilisasi_id');
    }
}
