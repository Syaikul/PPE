<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Salinan lokal satu endpoint master data.
 * Payload disimpan apa adanya (JSON) agar bentuk datanya identik dengan respons API.
 */
class MasterData extends Model
{
    protected $table = 'master_data';

    protected $fillable = [
        'endpoint',
        'payload',
        'jumlah',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    /** @return array<int, array<string, mixed>> */
    public function rows(): array
    {
        $decoded = json_decode($this->payload, true);

        return is_array($decoded) ? $decoded : [];
    }
}
