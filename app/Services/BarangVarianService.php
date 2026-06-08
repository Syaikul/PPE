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
