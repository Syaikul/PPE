<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MobilisasiPersonel extends Model
{
    protected $table = 'mobilisasi_personel';

    protected $fillable = [
        'mobilisasi_id',
        'personel_id',
        'submitted_at',
        'demob_status',
        'tanggal_demob',
        'demob_checked_at',
        'approved_at',
        'approval_catatan',
    ];

    protected $casts = [
        'submitted_at'     => 'datetime',
        'tanggal_demob'    => 'date',
        'demob_checked_at' => 'datetime',
        'approved_at'      => 'datetime',
    ];

    public const DEMOB_BELUM_CEK = 'belum_cek';
    public const DEMOB_MENUNGGU = 'menunggu_approval';
    public const DEMOB_SELESAI = 'selesai';

    public function mobilisasi(): BelongsTo
    {
        return $this->belongsTo(Mobilisasi::class, 'mobilisasi_id');
    }

    public function personel(): BelongsTo
    {
        return $this->belongsTo(Personel::class, 'personel_id');
    }

    public function posisi(): HasMany
    {
        return $this->hasMany(MobilisasiPersonelPosisi::class, 'mobilisasi_personel_id');
    }

    public function pengecekan(): HasMany
    {
        return $this->hasMany(MobilisasiPengecekan::class, 'mobilisasi_personel_id');
    }

    public function demobPengecekan(): HasMany
    {
        return $this->hasMany(DemobPengecekan::class, 'mobilisasi_personel_id');
    }
}
