<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpareBarangItem extends Model
{
    protected $table = 'spare_barang_item';

    protected $fillable = [
        'spare_barang_id',
        'idsubbarang',
        'idbarangvarian',
        'jumlah',
        'sisa',
        'returned_at',
    ];

    protected $casts = [
        'returned_at' => 'date',
    ];

    public function spareBarang(): BelongsTo
    {
        return $this->belongsTo(SpareBarang::class, 'spare_barang_id');
    }

    public function pemakaian(): HasMany
    {
        return $this->hasMany(SpareBarangPemakaian::class, 'spare_barang_item_id');
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }
}
