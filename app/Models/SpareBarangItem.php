<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }

    /** Qty terpakai = jumlah spare − sisa yang dikembalikan. */
    public function qtyDipakai(): int
    {
        return max(0, (int) $this->jumlah - (int) $this->sisa);
    }
}
