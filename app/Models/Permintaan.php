<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permintaan extends Model
{
    protected $table = 'permintaan';

    protected $fillable = [
        'idgudang',
        'nomor_mr',
        'tanggal_permintaan',
    ];

    protected $casts = [
        'tanggal_permintaan' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PermintaanItem::class, 'permintaan_id');
    }

    public function getStatusAttribute(): string
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->with('kedatangan')->get();

        if ($items->isEmpty()) {
            return 'Belum Selesai';
        }

        $statuses = $items->map(fn ($item) => $item->status);

        if ($statuses->every(fn ($s) => $s === 'Sudah Selesai')) {
            return 'Sudah Selesai';
        }

        if ($statuses->every(fn ($s) => $s === 'Belum Datang')) {
            return 'Belum Selesai';
        }

        return 'Sebagian';
    }
}
