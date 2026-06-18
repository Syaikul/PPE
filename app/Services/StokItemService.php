<?php

namespace App\Services;

use App\Models\Stok;

class StokItemService
{
    /** @return array{type: string, idsubbarang: ?int, idbarangvarian: ?int} */
    public static function parseItemKey(string $key): array
    {
        if (str_starts_with($key, 'sub:')) {
            return [
                'type'            => 'sub',
                'idsubbarang'     => (int) substr($key, 4),
                'idbarangvarian'  => null,
            ];
        }

        if (str_starts_with($key, 'varian:')) {
            return [
                'type'            => 'varian',
                'idsubbarang'     => null,
                'idbarangvarian'  => (int) substr($key, 8),
            ];
        }

        return [
            'type'            => 'varian',
            'idsubbarang'     => null,
            'idbarangvarian'  => (int) $key,
        ];
    }

    public static function itemKey(?int $idsubbarang, ?int $idbarangvarian): string
    {
        if ($idbarangvarian) {
            return 'varian:'.$idbarangvarian;
        }

        return 'sub:'.(int) $idsubbarang;
    }

    public static function existsInGudang(int $idgudang, ?int $idsubbarang, ?int $idbarangvarian, ?int $exceptId = null): bool
    {
        $query = Stok::where('idgudang', $idgudang);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        if ($idbarangvarian) {
            return $query->where('idbarangvarian', $idbarangvarian)->exists();
        }

        return $query->where('idsubbarang', $idsubbarang)->whereNull('idbarangvarian')->exists();
    }

    public static function labelForRow(
        Stok $stok,
        \Illuminate\Support\Collection $subBarangMap,
        \Illuminate\Support\Collection $varianMap
    ): string {
        if ($stok->idbarangvarian && $varianMap->has($stok->idbarangvarian)) {
            return $varianMap[$stok->idbarangvarian]['label'];
        }

        if ($stok->idsubbarang && $subBarangMap->has($stok->idsubbarang)) {
            return $subBarangMap[$stok->idsubbarang]['label'];
        }

        if ($stok->idbarangvarian) {
            return 'Varian #'.$stok->idbarangvarian;
        }

        return 'Sub Barang #'.($stok->idsubbarang ?? '-');
    }

    public static function kodeForRow(
        Stok $stok,
        \Illuminate\Support\Collection $subBarangMap,
        \Illuminate\Support\Collection $varianMap
    ): string {
        if ($stok->idbarangvarian && $varianMap->has($stok->idbarangvarian)) {
            return $varianMap[$stok->idbarangvarian]['kode'] ?? '-';
        }

        if ($stok->idsubbarang && $subBarangMap->has($stok->idsubbarang)) {
            return $subBarangMap[$stok->idsubbarang]['kode'] ?? '-';
        }

        return '-';
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    public static function kategoriMapsForGudang(int $idgudang): array
    {
        $rows = Stok::where('idgudang', $idgudang)->get();

        $byVarian = $rows
            ->filter(fn (Stok $s) => $s->idbarangvarian)
            ->mapWithKeys(fn (Stok $s) => [$s->idbarangvarian => $s->kategori ?? Stok::KATEGORI_NON_CONSUMABLE]);

        $bySub = $rows
            ->filter(fn (Stok $s) => $s->idsubbarang && ! $s->idbarangvarian)
            ->mapWithKeys(fn (Stok $s) => [$s->idsubbarang => $s->kategori ?? Stok::KATEGORI_NON_CONSUMABLE]);

        return [$byVarian, $bySub];
    }

    /** @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection} */
    public static function buildSubBarangKategoriData(int $idgudang, array $barangList): array
    {
        [$byVarian, $bySub] = self::kategoriMapsForGudang($idgudang);
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $kategoriMap = BarangVarianService::buildKategoriMap($barangList, $byVarian, $bySub);

        return [$subBarangMap, $kategoriMap];
    }
}
