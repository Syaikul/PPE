<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokPersen extends Model
{
    protected $table = 'stok_persen';

    protected $fillable = [
        'idgudang',
        'idsubbarang',
        'persen',
    ];

    protected $casts = [
        'persen' => 'float',
    ];
}
