<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpareBarang extends Model
{
    protected $table = 'spare_barang';

    protected $fillable = [
        'idgudang',
        'no_sr',
        'personel_id',
        'tanggal',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SpareBarangItem::class, 'spare_barang_id');
    }

    public function personel(): BelongsTo
    {
        return $this->belongsTo(Personel::class, 'personel_id');
    }

    /** SR sudah dikembalikan bila semua itemnya sudah dikembalikan. */
    public function isReturned(): bool
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return $items->isNotEmpty() && $items->every(fn ($item) => $item->returned_at !== null);
    }
}
