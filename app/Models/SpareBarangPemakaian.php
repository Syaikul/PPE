<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpareBarangPemakaian extends Model
{
    protected $table = 'spare_barang_pemakaian';

    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'spare_barang_item_id',
        'personel_id',
        'qty',
        'status',
        'catatan',
        'approval_catatan',
        'tanggal',
        'approved_at',
    ];

    protected $casts = [
        'tanggal'     => 'date',
        'approved_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(SpareBarangItem::class, 'spare_barang_item_id');
    }

    public function personel(): BelongsTo
    {
        return $this->belongsTo(Personel::class, 'personel_id');
    }

    public function isMenunggu(): bool
    {
        return $this->status === self::STATUS_MENUNGGU;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            default               => 'Menunggu Approval',
        };
    }
}
