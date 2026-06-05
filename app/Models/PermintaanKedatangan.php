<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanKedatangan extends Model
{
    protected $table = 'permintaan_kedatangan';

    protected $fillable = [
        'permintaan_item_id',
        'tanggal',
        'qty_datang',
        'no_po',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PermintaanItem::class, 'permintaan_item_id');
    }
}
