<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemobPengecekan extends Model
{
    protected $table = 'demob_pengecekan';

    protected $fillable = [
        'mobilisasi_personel_id',
        'idsubbarang',
        'kondisi',
        'catatan',
    ];

    public const KONDISI_LAYAK = 'layak';
    public const KONDISI_TIDAK_LAYAK = 'tidak_layak';
    public const KONDISI_HILANG = 'hilang';

    public function personel(): BelongsTo
    {
        return $this->belongsTo(MobilisasiPersonel::class, 'mobilisasi_personel_id');
    }

    public function isBermasalah(): bool
    {
        return in_array($this->kondisi, [self::KONDISI_TIDAK_LAYAK, self::KONDISI_HILANG], true);
    }
}
