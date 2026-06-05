<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermintaanItem extends Model
{
    protected $table = 'permintaan_item';

    protected $fillable = [
        'permintaan_id',
        'idbarangvarian',
        'qty_diminta',
    ];

    public function permintaan(): BelongsTo
    {
        return $this->belongsTo(Permintaan::class, 'permintaan_id');
    }

    public function kedatangan(): HasMany
    {
        return $this->hasMany(PermintaanKedatangan::class, 'permintaan_item_id');
    }

    public function getQtyDatangAttribute(): int
    {
        $kedatangan = $this->relationLoaded('kedatangan') ? $this->kedatangan : $this->kedatangan()->get();

        return (int) $kedatangan->sum('qty_datang');
    }

    public function getSisaAttribute(): int
    {
        return max(0, $this->qty_diminta - $this->qty_datang);
    }

    public function getStatusAttribute(): string
    {
        $datang = $this->qty_datang;

        if ($datang <= 0) {
            return 'Belum Datang';
        }

        if ($datang >= $this->qty_diminta) {
            return 'Sudah Selesai';
        }

        return 'Sebagian';
    }
}
