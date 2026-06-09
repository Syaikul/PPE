<?php

namespace App\Services;

use App\Models\DemobPengecekan;
use App\Models\PpeKeluar;

/**
 * Kepemilikan PPE melekat ke ORANG (idpersonel), lintas gudang.
 * Hanya berlaku untuk item Non Consumable.
 *
 * Owned usable = total qty keluar (semua gudang) - jumlah unit yang
 * saat demob dinyatakan Tidak Layak / Hilang.
 */
class PpeOwnershipService
{
    public static function issuedQty(int $idpersonel, int $idsubbarang): int
    {
        return (int) PpeKeluar::where('idpersonel', $idpersonel)
            ->where('idsubbarang', $idsubbarang)
            ->sum('qty');
    }

    public static function lostQty(int $idpersonel, int $idsubbarang): int
    {
        return DemobPengecekan::where('idsubbarang', $idsubbarang)
            ->whereIn('kondisi', [DemobPengecekan::KONDISI_TIDAK_LAYAK, DemobPengecekan::KONDISI_HILANG])
            ->whereHas('personel.personel', fn ($q) => $q->where('idpersonel', $idpersonel))
            ->count();
    }

    public static function ownedUsableQty(int $idpersonel, int $idsubbarang): int
    {
        return max(0, self::issuedQty($idpersonel, $idsubbarang) - self::lostQty($idpersonel, $idsubbarang));
    }

    public static function owns(int $idpersonel, int $idsubbarang, int $needed = 1): bool
    {
        return self::ownedUsableQty($idpersonel, $idsubbarang) >= $needed;
    }

    /** Catatan demob terakhir yang menyebabkan item dianggap hilang/rusak (untuk remark re-issue). */
    public static function latestProblemNote(int $idpersonel, int $idsubbarang): ?string
    {
        return DemobPengecekan::where('idsubbarang', $idsubbarang)
            ->whereIn('kondisi', [DemobPengecekan::KONDISI_TIDAK_LAYAK, DemobPengecekan::KONDISI_HILANG])
            ->whereHas('personel.personel', fn ($q) => $q->where('idpersonel', $idpersonel))
            ->latest('id')
            ->value('catatan');
    }
}
