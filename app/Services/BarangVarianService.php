<?php

namespace App\Services;

class BarangVarianService
{
    /**
     * Flatten semua varian (untuk varianMap / label varian spesifik).
     */
    public static function buildOptions(array $barangList): array
    {
        return self::buildFlatVarianOptions($barangList);
    }

    public static function buildFlatVarianOptions(array $barangList): array
    {
        $options = [];

        foreach ($barangList as $barang) {
            foreach ($barang['sub_barang'] ?? [] as $subBarang) {
                foreach ($subBarang['varian'] ?? [] as $varian) {
                    $options[] = [
                        'idvarian' => $varian['idvarian'],
                        'label'    => self::buildLabel($barang, $subBarang, $varian),
                        'kode'     => $varian['kode_lengkap'] ?? $subBarang['kode_lengkap'] ?? '',
                    ];
                }
            }

            foreach ($barang['varian'] ?? [] as $varian) {
                $options[] = [
                    'idvarian' => $varian['idvarian'],
                    'label'    => self::displayNameVarian($varian),
                    'kode'     => $varian['kode_lengkap'] ?? '',
                ];
            }
        }

        return $options;
    }

    /**
     * Opsi untuk form Stok: sub barang jika tidak ada varian nyata, per-varian jika ada.
     *
     * @return array<int, array{key: string, type: string, idsubbarang: int|null, idvarian: int|null, label: string, kode: string}>
     */
    public static function buildStokOptions(array $barangList): array
    {
        $options = [];

        foreach ($barangList as $barang) {
            foreach ($barang['sub_barang'] ?? [] as $subBarang) {
                if (self::hasRealVariants($subBarang)) {
                    foreach ($subBarang['varian'] ?? [] as $varian) {
                        if (self::isDefaultVariant($varian)) {
                            continue;
                        }

                        $options[] = [
                            'key'         => 'varian:'.$varian['idvarian'],
                            'type'        => 'varian',
                            'idsubbarang' => $subBarang['idsubbarang'],
                            'idvarian'    => $varian['idvarian'],
                            'label'       => self::buildLabel($barang, $subBarang, $varian),
                            'kode'        => $varian['kode_lengkap'] ?? $subBarang['kode_lengkap'] ?? '',
                        ];
                    }
                } else {
                    $options[] = [
                        'key'         => 'sub:'.$subBarang['idsubbarang'],
                        'type'        => 'sub',
                        'idsubbarang' => $subBarang['idsubbarang'],
                        'idvarian'    => null,
                        'label'       => self::displayNameSubBarang($subBarang),
                        'kode'        => $subBarang['kode_lengkap'] ?? '',
                    ];
                }
            }

            foreach ($barang['varian'] ?? [] as $varian) {
                $options[] = [
                    'key'         => 'varian:'.$varian['idvarian'],
                    'type'        => 'varian',
                    'idsubbarang' => null,
                    'idvarian'    => $varian['idvarian'],
                    'label'       => self::displayNameVarian($varian),
                    'kode'        => $varian['kode_lengkap'] ?? '',
                ];
            }
        }

        return $options;
    }

    public static function isDefaultVariant(array $varian): bool
    {
        return ($varian['is_default'] ?? false)
            || ($varian['namavarian'] ?? '') === '-'
            || ($varian['nama_tampilan'] ?? '') === '-';
    }

    public static function hasRealVariants(array $subBarang): bool
    {
        return collect($subBarang['varian'] ?? [])
            ->contains(fn ($v) => ! self::isDefaultVariant($v));
    }

    /** @return array<int, int> */
    public static function realVarianIds(array $subBarang): array
    {
        return collect($subBarang['varian'] ?? [])
            ->filter(fn ($v) => ! self::isDefaultVariant($v))
            ->pluck('idvarian')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /** Semua id varian sub barang (termasuk default) — untuk kompatibilitas data lama. */
    public static function allVarianIds(array $subBarang): array
    {
        return collect($subBarang['varian'] ?? [])
            ->pluck('idvarian')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public static function buildOptionsLegacy(array $barangList): array
    {
        return self::buildFlatVarianOptions($barangList);
    }

    public static function buildMap(array $barangList): \Illuminate\Support\Collection
    {
        return collect(self::buildFlatVarianOptions($barangList))->keyBy('idvarian');
    }

    /**
     * Opsi berbasis SUB BARANG (dipakai posisippe & mobilisasi).
     * Tiap sub barang membawa daftar idvarian miliknya agar bisa
     * dipetakan ke kategori (Consumable/Non Consumable) dari tabel Stok.
     */
    public static function buildSubBarangOptions(array $barangList): array
    {
        $options = [];

        foreach ($barangList as $barang) {
            foreach ($barang['sub_barang'] ?? [] as $subBarang) {
                $realVarianIds = self::realVarianIds($subBarang);
                $allVarianIds = self::allVarianIds($subBarang);

                $options[] = [
                    'idsubbarang'       => $subBarang['idsubbarang'],
                    'label'             => self::displayNameSubBarang($subBarang),
                    'kode'              => $subBarang['kode_lengkap'] ?? '',
                    'has_real_variants' => ! empty($realVarianIds),
                    'varian_ids'        => $realVarianIds,
                    'all_varian_ids'    => $allVarianIds,
                ];
            }
        }

        return $options;
    }

    public static function buildSubBarangMap(array $barangList): \Illuminate\Support\Collection
    {
        return collect(self::buildSubBarangOptions($barangList))->keyBy('idsubbarang');
    }

    /**
     * Petakan idsubbarang => kategori (Consumable / Non Consumable) berdasarkan
     * kategori yang tersimpan di tabel Stok (per idbarangvarian).
     * Default 'Non Consumable' bila barang belum ada di stok.
     *
     * @param  array  $barangList  hasil API barang-with-varian
     * @param  \Illuminate\Support\Collection  $stokKategoriByVarian  idbarangvarian => kategori
     * @param  \Illuminate\Support\Collection  $stokKategoriBySub  idsubbarang => kategori (stok level sub)
     */
    public static function buildKategoriMap(
        array $barangList,
        \Illuminate\Support\Collection $stokKategoriByVarian,
        \Illuminate\Support\Collection $stokKategoriBySub = new \Illuminate\Support\Collection
    ): \Illuminate\Support\Collection {
        $map = [];

        foreach (self::buildSubBarangOptions($barangList) as $sub) {
            $kategori = 'Non Consumable';

            if ($sub['has_real_variants']) {
                foreach ($sub['varian_ids'] as $idvarian) {
                    $stokKat = $stokKategoriByVarian->get($idvarian);
                    if ($stokKat === 'Consumable') {
                        $kategori = 'Consumable';
                        break;
                    }
                    if ($stokKat === 'Non Consumable') {
                        $kategori = 'Non Consumable';
                    }
                }
            } else {
                $stokKat = $stokKategoriBySub->get($sub['idsubbarang']);
                if ($stokKat) {
                    $kategori = $stokKat;
                } else {
                    foreach ($sub['all_varian_ids'] as $idvarian) {
                        $legacyKat = $stokKategoriByVarian->get($idvarian);
                        if ($legacyKat === 'Consumable') {
                            $kategori = 'Consumable';
                            break;
                        }
                        if ($legacyKat === 'Non Consumable') {
                            $kategori = 'Non Consumable';
                        }
                    }
                }
            }

            $map[$sub['idsubbarang']] = $kategori;
        }

        return collect($map);
    }

    /** Label level terakhir: sub saja / default varian → nama sub; varian nyata → nama varian. */
    private static function buildLabel(array $barang, array $subBarang, array $varian): string
    {
        if (self::isDefaultVariant($varian)) {
            return self::displayNameSubBarang($subBarang);
        }

        return self::displayNameVarian($varian);
    }

    private static function displayNameSubBarang(array $subBarang): string
    {
        return trim((string) ($subBarang['nama_tampilan'] ?? $subBarang['namasubbarang'] ?? ''));
    }

    private static function displayNameVarian(array $varian): string
    {
        return trim((string) ($varian['nama_tampilan'] ?? $varian['namavarian'] ?? ''));
    }
}
