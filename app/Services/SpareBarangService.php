<?php

namespace App\Services;

use App\Models\PpeKeluar;
use App\Models\SpareBarang;
use App\Models\SpareBarangItem;
use App\Models\SpareBarangPemakaian;
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
    public static function createSr(int $idgudang, string $noSr, ?int $personelId, string $tanggal, array $items): SpareBarang
    {
        $filtered = self::filterItems($items);

        return DB::transaction(function () use ($idgudang, $noSr, $personelId, $tanggal, $filtered) {
            $sr = SpareBarang::create([
                'idgudang'    => $idgudang,
                'no_sr'       => $noSr,
                'personel_id' => $personelId,
                'tanggal'     => $tanggal,
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

    /** Ajukan pemakaian spare ke personel penerima; menunggu Approval Demob. */
    public static function ajukanPemakaian(SpareBarangItem $item, int $personelId, int $qty, ?string $catatan): void
    {
        if ($item->isReturned()) {
            throw new \RuntimeException('Item spare ini sudah dikembalikan ke stok.');
        }

        $menunggu = (int) $item->pemakaian()
            ->where('status', SpareBarangPemakaian::STATUS_MENUNGGU)
            ->sum('qty');

        if ($qty > ($item->sisa - $menunggu)) {
            $tersedia = max(0, $item->sisa - $menunggu);

            throw new \RuntimeException("Sisa spare tidak cukup (tersedia: {$tersedia}, termasuk yang menunggu approval).");
        }

        SpareBarangPemakaian::create([
            'spare_barang_item_id' => $item->id,
            'personel_id'          => $personelId,
            'qty'                  => $qty,
            'status'               => SpareBarangPemakaian::STATUS_MENUNGGU,
            'catatan'              => $catatan,
            'tanggal'              => now()->toDateString(),
        ]);
    }

    /** Approval disetujui: sisa berkurang & tercatat di PPE Keluar. */
    public static function approvePemakaian(SpareBarangPemakaian $pemakaian, ?string $approvalCatatan): void
    {
        if (! $pemakaian->isMenunggu()) {
            throw new \RuntimeException('Pemakaian spare ini sudah diproses.');
        }

        DB::transaction(function () use ($pemakaian, $approvalCatatan) {
            $item = SpareBarangItem::where('id', $pemakaian->spare_barang_item_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($pemakaian->qty > $item->sisa) {
                throw new \RuntimeException('Sisa spare tidak cukup untuk disetujui.');
            }

            $sr = $item->spareBarang;

            $item->decrement('sisa', $pemakaian->qty);

            PpeKeluar::create([
                'idgudang'       => $sr->idgudang,
                'idpersonel'     => $pemakaian->personel->idpersonel,
                'idsubbarang'    => $item->idsubbarang,
                'idbarangvarian' => $item->idbarangvarian,
                'qty'            => $pemakaian->qty,
                'tanggal'        => now()->toDateString(),
                'catatan'        => 'Spare Barang SR '.$sr->no_sr,
                'personel_id'    => $pemakaian->personel_id,
                'mobilisasi_id'  => null,
            ]);

            $pemakaian->update([
                'status'           => SpareBarangPemakaian::STATUS_APPROVED,
                'approval_catatan' => $approvalCatatan,
                'approved_at'      => now(),
            ]);
        });
    }

    public static function rejectPemakaian(SpareBarangPemakaian $pemakaian, ?string $approvalCatatan): void
    {
        if (! $pemakaian->isMenunggu()) {
            throw new \RuntimeException('Pemakaian spare ini sudah diproses.');
        }

        $pemakaian->update([
            'status'           => SpareBarangPemakaian::STATUS_REJECTED,
            'approval_catatan' => $approvalCatatan,
        ]);
    }

    /** Kembalikan sisa spare ke stok gudang. */
    public static function kembalikan(SpareBarang $sr): int
    {
        $items = $sr->items()->whereNull('returned_at')->get();

        if ($items->isEmpty()) {
            throw new \RuntimeException('Semua item pada SR ini sudah dikembalikan.');
        }

        return DB::transaction(function () use ($sr, $items) {
            $totalDikembalikan = 0;

            foreach ($items as $item) {
                $item->pemakaian()
                    ->where('status', SpareBarangPemakaian::STATUS_MENUNGGU)
                    ->update([
                        'status'           => SpareBarangPemakaian::STATUS_REJECTED,
                        'approval_catatan' => 'Dibatalkan karena spare dikembalikan ke stok.',
                    ]);

                if ($item->sisa > 0) {
                    $stok = self::findStok($sr->idgudang, $item->idsubbarang, $item->idbarangvarian);

                    if (! $stok) {
                        throw new \RuntimeException('Barang spare tidak ditemukan di stok gudang. Tambahkan di Data Stok terlebih dahulu.');
                    }

                    $stok = Stok::where('id', $stok->id)->lockForUpdate()->firstOrFail();
                    $stok->increment('qty', $item->sisa);

                    $totalDikembalikan += $item->sisa;
                }

                $item->update([
                    'sisa'        => 0,
                    'returned_at' => now()->toDateString(),
                ]);
            }

            return $totalDikembalikan;
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
