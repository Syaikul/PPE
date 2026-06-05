<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Personel extends Model
{
    protected $table = 'personel';

    protected $fillable = [
        'idgudang',
        'idpersonel',
        'status',
    ];

    public function posisi(): HasMany
    {
        return $this->hasMany(PersonelPosisi::class, 'personel_id');
    }
}
