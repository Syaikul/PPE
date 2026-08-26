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
        // Jumlah UNIT yang rusak/hilang, bukan jumlah baris pengecekan.
        // Data lama tanpa qty_bermasalah dihitung 1 unit per baris.
        return (int) DemobPengecekan::where('idsubbarang', $idsubbarang)
            ->whereIn('kondisi', [DemobPengecekan::KONDISI_TIDAK_LAYAK, DemobPengecekan::KONDISI_HILANG])
            ->whereHas('personel.personel', fn ($q) => $q->where('idpersonel', $idpersonel))
            ->get()
            ->sum(fn ($d) => $d->qtyBermasalah());
    }

    public static function ownedUsableQty(int $idpersonel, int $idsubbarang): int
    {
        return max(0, self::issuedQty($idpersonel, $idsubbarang) - self::lostQty($idpersonel, $idsubbarang));
    }

    public static function owns(int $idpersonel, int $idsubbarang, int $needed = 1): bool
    {
        return self::ownedUsableQty($idpersonel, $idsubbarang) >= $needed;
    }

    /**
     * Riwayat pengeluaran per varian (semua mob), untuk petunjuk saat menambah kekurangan.
     *
     * @return array<int, array{idbarangvarian: int|null, qty: int}>
     */
    public static function issuedByVarian(int $idpersonel, int $idsubbarang): array
    {
        return PpeKeluar::where('idpersonel', $idpersonel)
            ->where('idsubbarang', $idsubbarang)
            ->get()
            ->groupBy(fn ($row) => $row->idbarangvarian ?? 0)
            ->map(fn ($rows, $idvarian) => [
                'idbarangvarian' => ((int) $idvarian) > 0 ? (int) $idvarian : null,
                'qty'            => (int) $rows->sum('qty'),
            ])
            ->sortByDesc('qty')
            ->values()
            ->all();
    }

    /**
     * Penjelasan kekurangan untuk pengecekan mob.
     * Contoh: kebutuhan 2, pernah keluar 2, 1 rusak → "Personel kurang 1 pcs".
     */
    public static function shortageNote(int $idpersonel, int $idsubbarang, int $needed): ?string
    {
        $owned = self::ownedUsableQty($idpersonel, $idsubbarang);
        $lost = self::lostQty($idpersonel, $idsubbarang);
        $kurang = max(0, $needed - $owned);

        if ($kurang <= 0) {
            return null;
        }

        if ($owned <= 0 && $lost <= 0) {
            return $needed > 1 ? "Personel kurang {$kurang} pcs." : null;
        }

        $parts = ["Kebutuhan {$needed} pcs."];

        if ($owned > 0) {
            $parts[] = "Sudah punya {$owned} pcs.";
        }

        if ($lost > 0) {
            $parts[] = "{$lost} pcs rusak/hilang dari mob sebelumnya.";
        }

        $parts[] = "Personel kurang {$kurang} pcs.";

        return implode(' ', $parts);
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
