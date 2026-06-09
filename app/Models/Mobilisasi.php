<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mobilisasi extends Model
{
    protected $table = 'mobilisasi';

    protected $fillable = [
        'idgudang',
        'sr',
        'lokasi_pekerjaan',
        'status',
    ];

    public function personel(): HasMany
    {
        return $this->hasMany(MobilisasiPersonel::class, 'mobilisasi_id');
    }

    public function perlengkapan(): HasMany
    {
        return $this->hasMany(MobilisasiPerlengkapan::class, 'mobilisasi_id');
    }
}
