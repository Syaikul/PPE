<?php

namespace App\Services;

class BarangVarianService
{
    /**
     * Flatten response API barang-with-varian menjadi opsi stok.
     * Termasuk varian default (namavarian = "-") untuk sub barang tanpa ukuran.
     */
    public static function buildOptions(array $barangList): array
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

            // Format lama: varian langsung di barang
            foreach ($barang['varian'] ?? [] as $varian) {
                $options[] = [
                    'idvarian' => $varian['idvarian'],
                    'label'    => trim(
                        ($barang['namabarang'] ?? '') . ' — ' .
                        ($varian['nama_tampilan'] ?? $varian['namavarian'] ?? '')
                    ),
                    'kode'     => $varian['kode_lengkap'] ?? '',
                ];
            }
        }

        return $options;
    }

    public static function buildMap(array $barangList): \Illuminate\Support\Collection
    {
        return collect(self::buildOptions($barangList))->keyBy('idvarian');
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
                $varianIds = collect($subBarang['varian'] ?? [])
                    ->pluck('idvarian')
                    ->filter()
                    ->values()
                    ->all();

                $options[] = [
                    'idsubbarang' => $subBarang['idsubbarang'],
                    'label'       => trim(
                        ($barang['namabarang'] ?? '') . ' ' .
                        ($subBarang['nama_tampilan'] ?? $subBarang['namasubbarang'] ?? '')
                    ),
                    'kode'        => $subBarang['kode_lengkap'] ?? '',
                    'varian_ids'  => $varianIds,
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
     */
    public static function buildKategoriMap(array $barangList, \Illuminate\Support\Collection $stokKategoriByVarian): \Illuminate\Support\Collection
    {
        $map = [];

        foreach (self::buildSubBarangOptions($barangList) as $sub) {
            $kategori = 'Non Consumable';

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

            $map[$sub['idsubbarang']] = $kategori;
        }

        return collect($map);
    }

    private static function buildLabel(array $barang, array $subBarang, array $varian): string
    {
        $isDefault = ($varian['is_default'] ?? false)
            || ($varian['namavarian'] ?? '') === '-';

        if ($isDefault) {
            return trim(
                ($barang['namabarang'] ?? '') . ' ' .
                ($subBarang['nama_tampilan'] ?? $subBarang['namasubbarang'] ?? '')
            );
        }

        return trim(
            ($barang['namabarang'] ?? '') . ' ' .
            ($subBarang['nama_tampilan'] ?? $subBarang['namasubbarang'] ?? '') . ' ' .
            ($varian['nama_tampilan'] ?? $varian['namavarian'] ?? '')
        );
    }
}
