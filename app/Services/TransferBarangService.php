<?php

namespace App\Services;

use App\Models\Permintaan;
use App\Models\PermintaanItem;
use App\Models\PermintaanKedatangan;
use App\Models\PpeKeluar;
use App\Models\Stok;
use Illuminate\Support\Facades\DB;

class TransferBarangService
{
    /**
     * @param  array<int, array{stok_id: int, qty: int}>  $items
     * @return array{ok: bool, error?: string}
     */
    public static function validate(
        int $idgudangAsal,
        int $idgudangTujuan,
        array $items,
        \Illuminate\Support\Collection $subBarangMap,
        \Illuminate\Support\Collection $varianMap
    ): array {
        if ($idgudangAsal === $idgudangTujuan) {
            return ['ok' => false, 'error' => 'Gudang tujuan tidak boleh sama dengan gudang asal.'];
        }

        if (! MasterApiService::isGudangTransferTujuan($idgudangTujuan)) {
            $label = MasterApiService::gudangTransferTujuanLabel();

            return ['ok' => false, 'error' => "Transfer barang hanya dapat dilakukan ke gudang {$label}."];
        }

        $gudangTujuan = MasterApiService::gudangById($idgudangTujuan);
        if (! $gudangTujuan) {
            return ['ok' => false, 'error' => 'Gudang tujuan tidak ditemukan.'];
        }

        foreach ($items as $item) {
            $stok = Stok::where('idgudang', $idgudangAsal)->find($item['stok_id']);
            if (! $stok) {
                return ['ok' => false, 'error' => 'Data stok tidak valid.'];
            }

            $qty = (int) $item['qty'];
            if ($qty < 1) {
                continue;
            }

            if ($qty > $stok->qty) {
                $label = StokItemService::labelForRow($stok, $subBarangMap, $varianMap);

                return [
                    'ok'    => false,
                    'error' => "Jumlah transfer \"{$label}\" ({$qty}) melebihi stok tersedia ({$stok->qty}).",
                ];
            }

            if (! StokItemService::existsInGudang($idgudangTujuan, $stok->idsubbarang, $stok->idbarangvarian)) {
                $label = StokItemService::labelForRow($stok, $subBarangMap, $varianMap);
                $namaGudang = $gudangTujuan['namagudang'] ?? 'Gudang #'.$idgudangTujuan;

                return [
                    'ok'    => false,
                    'error' => "Barang \"{$label}\" belum terdaftar di stok {$namaGudang}. Tambahkan barang tersebut di Data Stok gudang tujuan terlebih dahulu.",
                ];
            }
        }

        return ['ok' => true];
    }

    /**
     * @param  array<int, array{stok_id: int, qty: int}>  $items
     */
    public static function execute(
        int $idgudangAsal,
        int $idgudangTujuan,
        string $tanggal,
        array $items,
        string $namaGudangAsal,
        string $namaGudangTujuan
    ): void {
        $penerimaKeluar = 'Di transfer Untuk '.$namaGudangTujuan;
        $nomorMrMasuk = 'Di transfer dari '.$namaGudangAsal;

        DB::transaction(function () use ($idgudangAsal, $idgudangTujuan, $tanggal, $items, $penerimaKeluar, $nomorMrMasuk) {
            $permintaan = Permintaan::create([
                'idgudang'           => $idgudangTujuan,
                'nomor_mr'           => $nomorMrMasuk,
                'tanggal_permintaan' => $tanggal,
            ]);

            foreach ($items as $item) {
                $qty = (int) $item['qty'];
                if ($qty < 1) {
                    continue;
                }

                $stokAsal = Stok::where('idgudang', $idgudangAsal)
                    ->lockForUpdate()
                    ->findOrFail($item['stok_id']);

                if ($qty > $stokAsal->qty) {
                    throw new \RuntimeException('Stok tidak cukup saat transfer.');
                }

                $stokAsal->decrement('qty', $qty);

                $stokTujuan = Stok::where('idgudang', $idgudangTujuan)
                    ->when($stokAsal->idbarangvarian, fn ($q) => $q->where('idbarangvarian', $stokAsal->idbarangvarian))
                    ->when(! $stokAsal->idbarangvarian, fn ($q) => $q->where('idsubbarang', $stokAsal->idsubbarang)->whereNull('idbarangvarian'))
                    ->lockForUpdate()
                    ->firstOrFail();

                $stokTujuan->increment('qty', $qty);

                PpeKeluar::create([
                    'idgudang'       => $idgudangAsal,
                    'idpersonel'     => null,
                    'idsubbarang'    => $stokAsal->idsubbarang,
                    'idbarangvarian' => $stokAsal->idbarangvarian,
                    'qty'            => $qty,
                    'tanggal'        => $tanggal,
                    'catatan'        => $penerimaKeluar,
                    'personel_id'    => null,
                    'mobilisasi_id'  => null,
                ]);

                $permintaanItem = PermintaanItem::create([
                    'permintaan_id'  => $permintaan->id,
                    'idsubbarang'    => $stokAsal->idsubbarang,
                    'idbarangvarian' => $stokAsal->idbarangvarian,
                    'qty_diminta'    => $qty,
                ]);

                PermintaanKedatangan::create([
                    'permintaan_item_id' => $permintaanItem->id,
                    'tanggal'            => $tanggal,
                    'qty_datang'         => $qty,
                    'no_po'              => null,
                    'catatan'            => null,
                ]);
            }
        });
    }
}
