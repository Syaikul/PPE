<?php

namespace App\Services;

use App\Models\PpeKeluar;
use App\Models\SpareBarang;
use App\Models\SpareBarangItem;
use App\Models\Stok;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SpareBarangService
{
    /**
     * @param  array<int, array{stok_id: int, qty: int}>  $items
     * @return array{ok: bool, error?: string}
     */
    public static function validateSr(int $idgudang, array $items, Collection $subBarangMap, Collection $varianMap): array
    {
        $filtered = self::filterItems($items);

        if (empty($filtered)) {
            return ['ok' => false, 'error' => 'Pilih minimal 1 item dengan jumlah lebih dari 0.'];
        }

        foreach ($filtered as $item) {
            $stok = Stok::where('idgudang', $idgudang)->find($item['stok_id']);
            if (! $stok) {
                return ['ok' => false, 'error' => 'Data stok tidak valid.'];
            }

            if ($item['qty'] > $stok->qty) {
                $label = StokItemService::labelForRow($stok, $subBarangMap, $varianMap);

                return [
                    'ok'    => false,
                    'error' => "Jumlah spare \"{$label}\" ({$item['qty']}) melebihi stok tersedia ({$stok->qty}).",
                ];
            }
        }

        return ['ok' => true];
    }

    /**
     * Buat SR spare barang: stok gudang langsung dikurangi karena barang dialokasikan sebagai spare.
     *
     * @param  array<int, array{stok_id: int, qty: int}>  $items
     */
    public static function createSr(int $idgudang, string $noSr, ?int $personelId, string $tanggal, array $items, ?int $mobilisasiId = null): SpareBarang
    {
        $filtered = self::filterItems($items);

        return DB::transaction(function () use ($idgudang, $noSr, $personelId, $tanggal, $filtered, $mobilisasiId) {
            $sr = SpareBarang::create([
                'idgudang'      => $idgudang,
                'mobilisasi_id' => $mobilisasiId,
                'no_sr'         => $noSr,
                'personel_id'   => $personelId,
                'tanggal'       => $tanggal,
            ]);

            foreach ($filtered as $item) {
                $stok = Stok::where('idgudang', $idgudang)->lockForUpdate()->findOrFail($item['stok_id']);

                if ($item['qty'] > $stok->qty) {
                    throw new \RuntimeException('Stok tidak cukup saat pembuatan spare barang.');
                }

                $stok->decrement('qty', $item['qty']);

                SpareBarangItem::create([
                    'spare_barang_id' => $sr->id,
                    'idsubbarang'     => $stok->idsubbarang,
                    'idbarangvarian'  => $stok->idbarangvarian,
                    'jumlah'          => $item['qty'],
                    'sisa'            => $item['qty'],
                ]);
            }

            return $sr;
        });
    }

    /**
     * Selesaikan spare: sisa kembali ke stok, selisih (jumlah − sisa) langsung ke PPE Keluar.
     *
     * @param  array<int, int>  $sisaByItemId  spare_barang_item_id => qty yang dikembalikan
     * @return array{dikembalikan: int, dipakai: int}
     */
    public static function kembalikan(SpareBarang $sr, array $sisaByItemId, string $namaPenanggungJawab): array
    {
        $items = $sr->items()->whereNull('returned_at')->get();

        if ($items->isEmpty()) {
            throw new \RuntimeException('Semua item pada SR ini sudah dikembalikan.');
        }

        foreach ($items as $item) {
            if (! array_key_exists($item->id, $sisaByItemId)) {
                throw new \RuntimeException('Isi sisa untuk semua item spare.');
            }

            $sisa = (int) $sisaByItemId[$item->id];
            if ($sisa < 0 || $sisa > $item->sisa) {
                throw new \RuntimeException('Sisa spare tidak valid (0 sampai '.$item->sisa.').');
            }
        }

        return DB::transaction(function () use ($sr, $items, $sisaByItemId, $namaPenanggungJawab) {
            $totalDikembalikan = 0;
            $totalDipakai = 0;
            $idpersonel = $sr->personel?->idpersonel;

            foreach ($items as $item) {
                $sisaKembali = (int) $sisaByItemId[$item->id];
                $dipakai = (int) $item->sisa - $sisaKembali;

                if ($sisaKembali > 0) {
                    $stok = self::findStok($sr->idgudang, $item->idsubbarang, $item->idbarangvarian);

                    if (! $stok) {
                        throw new \RuntimeException('Barang spare tidak ditemukan di stok gudang. Tambahkan di Data Stok terlebih dahulu.');
                    }

                    $stok = Stok::where('id', $stok->id)->lockForUpdate()->firstOrFail();
                    $stok->increment('qty', $sisaKembali);
                    $totalDikembalikan += $sisaKembali;
                }

                if ($dipakai > 0) {
                    $jumlahMinta = (int) $item->jumlah;
                    $totalTerpakai = $jumlahMinta - $sisaKembali;

                    PpeKeluar::create([
                        'idgudang'       => $sr->idgudang,
                        'idpersonel'     => $idpersonel,
                        'idsubbarang'    => $item->idsubbarang,
                        'idbarangvarian' => $item->idbarangvarian,
                        'qty'            => $dipakai,
                        'tanggal'        => now()->toDateString(),
                        'catatan'        => $namaPenanggungJawab.' minta spare '.$jumlahMinta.' dan dipakai '.$totalTerpakai,
                        'personel_id'    => $sr->personel_id,
                        'mobilisasi_id'  => $sr->mobilisasi_id,
                    ]);

                    $totalDipakai += $dipakai;
                }

                $item->update([
                    'sisa'        => $sisaKembali,
                    'returned_at' => now()->toDateString(),
                ]);
            }

            return [
                'dikembalikan' => $totalDikembalikan,
                'dipakai'      => $totalDipakai,
            ];
        });
    }

    public static function findStok(int $idgudang, ?int $idsubbarang, ?int $idbarangvarian): ?Stok
    {
        $query = Stok::where('idgudang', $idgudang);

        if ($idbarangvarian) {
            return $query->where('idbarangvarian', $idbarangvarian)->first();
        }

        return $query->where('idsubbarang', $idsubbarang)->whereNull('idbarangvarian')->first();
    }

    public static function labelForItem(
        ?int $idsubbarang,
        ?int $idbarangvarian,
        Collection $subBarangMap,
        Collection $varianMap
    ): string {
        if ($idbarangvarian && $varianMap->has($idbarangvarian)) {
            return $varianMap[$idbarangvarian]['label'];
        }

        if ($idsubbarang && $subBarangMap->has($idsubbarang)) {
            return $subBarangMap[$idsubbarang]['label'];
        }

        return $idbarangvarian ? 'Varian #'.$idbarangvarian : 'Sub Barang #'.$idsubbarang;
    }

    /**
     * @param  array<int, array{stok_id: int, qty: int}>  $items
     * @return array<int, array{stok_id: int, qty: int}>
     */
    private static function filterItems(array $items): array
    {
        $filtered = [];

        foreach ($items as $item) {
            $qty = (int) ($item['qty'] ?? 0);

            if ($qty > 0) {
                $filtered[] = ['stok_id' => (int) $item['stok_id'], 'qty' => $qty];
            }
        }

        return $filtered;
    }
}
