<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stok extends Model
{
    protected $table = 'stok';

    public const KATEGORI_CONSUMABLE = 'Consumable';
    public const KATEGORI_NON_CONSUMABLE = 'Non Consumable';

    protected $fillable = [
        'idgudang',
        'idbarangvarian',
        'qty',
        'kategori',
    ];
}
