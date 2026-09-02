<?php

namespace App\Services;

use App\Models\Personel;
use App\Models\PpeKeluar;
use App\Models\Stok;
use Illuminate\Support\Facades\DB;

class PpeKeluarService
{
    /**
     * Catat barang keluar manual (bukan dari Mob-Demob / transfer).
     * Stok gudang langsung dikurangi.
     */
    public static function keluarkanManual(
        int $idgudang,
        int $stokId,
        int $qty,
        string $tanggal,
        Personel $personel,
        ?string $catatan
    ): PpeKeluar {
        return DB::transaction(function () use ($idgudang, $stokId, $qty, $tanggal, $personel, $catatan) {
            $stok = Stok::where('idgudang', $idgudang)->lockForUpdate()->find($stokId);

            if (! $stok) {
                throw new \RuntimeException('Data stok tidak valid.');
            }

            if ($qty > $stok->qty) {
                throw new \RuntimeException(
                    "Jumlah keluar ({$qty}) melebihi stok tersedia ({$stok->qty})."
                );
            }

            $idsubbarang = (int) $stok->idsubbarang;
            if ($idsubbarang < 1) {
                throw new \RuntimeException('Data stok tidak memiliki sub barang. Perbarui data stok terlebih dahulu.');
            }

            $stok->decrement('qty', $qty);

            return PpeKeluar::create([
                'idgudang'       => $idgudang,
                'idpersonel'     => $personel->idpersonel,
                'idsubbarang'    => $idsubbarang,
                'idbarangvarian' => $stok->idbarangvarian,
                'qty'            => $qty,
                'tanggal'        => $tanggal,
                'catatan'        => $catatan,
                'personel_id'    => $personel->id,
                'mobilisasi_id'  => null,
            ]);
        });
    }
}
