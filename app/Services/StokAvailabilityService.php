<?php

namespace App\Services;

use App\Models\Stok;

class StokAvailabilityService
{
    /** Total qty stok tersedia di gudang untuk varian-varian sub barang. */
    public static function availableQty(int $idgudang, array $varianIds): int
    {
        if (empty($varianIds)) {
            return 0;
        }

        return (int) Stok::where('idgudang', $idgudang)
            ->whereIn('idbarangvarian', $varianIds)
            ->sum('qty');
    }

    public static function inStok(int $idgudang, array $varianIds): bool
    {
        if (empty($varianIds)) {
            return false;
        }

        return Stok::where('idgudang', $idgudang)
            ->whereIn('idbarangvarian', $varianIds)
            ->exists();
    }

    /**
     * @return array{available: int, in_stok: bool, ok: bool}
     */
    public static function check(int $idgudang, array $varianIds, int $needed): array
    {
        if ($needed <= 0) {
            return ['available' => self::availableQty($idgudang, $varianIds), 'in_stok' => true, 'ok' => true];
        }

        $available = self::availableQty($idgudang, $varianIds);
        $inStok = self::inStok($idgudang, $varianIds);

        return [
            'available' => $available,
            'in_stok'   => $inStok,
            'ok'        => $inStok && $available >= $needed,
        ];
    }

    /** Cek & kurangi stok untuk satu varian spesifik. */
    public static function checkVarian(int $idgudang, int $idvarian, int $needed): array
    {
        return self::check($idgudang, [$idvarian], $needed);
    }

    public static function deductVarian(int $idgudang, int $idvarian, int $qty): void
    {
        self::deduct($idgudang, [$idvarian], $qty);
    }

    public static function qtyForVarian(int $idgudang, int $idvarian): int
    {
        return (int) Stok::where('idgudang', $idgudang)
            ->where('idbarangvarian', $idvarian)
            ->value('qty') ?? 0;
    }

    public static function varianInStok(int $idgudang, int $idvarian): bool
    {
        return Stok::where('idgudang', $idgudang)
            ->where('idbarangvarian', $idvarian)
            ->exists();
    }

    /** Kurangi stok gudang saat barang keluar (FIFO per baris stok). */
    public static function deduct(int $idgudang, array $varianIds, int $qty): void
    {
        if ($qty <= 0 || empty($varianIds)) {
            return;
        }

        $remaining = $qty;

        $stokRows = Stok::where('idgudang', $idgudang)
            ->whereIn('idbarangvarian', $varianIds)
            ->where('qty', '>', 0)
            ->orderBy('id')
            ->get();

        foreach ($stokRows as $stok) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($stok->qty, $remaining);
            $stok->decrement('qty', $take);
            $remaining -= $take;
        }
    }
}
