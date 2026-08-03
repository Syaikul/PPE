<?php

namespace App\Services;

use App\Models\PeminjamanPpe;
use App\Models\Stok;
use Illuminate\Support\Facades\DB;

class PeminjamanPpeService
{
    /**
     * @return array{ok: bool, error?: string}
     */
    public static function validatePengajuan(
        int $idgudangPeminjam,
        int $idgudangSumber,
        ?int $idsubbarang,
        ?int $idbarangvarian,
        int $qty,
        \Illuminate\Support\Collection $subBarangMap,
        \Illuminate\Support\Collection $varianMap
    ): array {
        if ($idgudangPeminjam === $idgudangSumber) {
            return ['ok' => false, 'error' => 'Sumber peminjaman tidak boleh sama dengan gudang Anda.'];
        }

        if ($qty < 1) {
            return ['ok' => false, 'error' => 'Qty harus lebih dari 0.'];
        }

        $gudangSumber = MasterApiService::gudangById($idgudangSumber);
        if (! $gudangSumber) {
            return ['ok' => false, 'error' => 'Gudang sumber tidak ditemukan.'];
        }

        if (! StokItemService::existsInGudang($idgudangPeminjam, $idsubbarang, $idbarangvarian)) {
            $label = self::labelForItem($idsubbarang, $idbarangvarian, $subBarangMap, $varianMap);

            return [
                'ok'    => false,
                'error' => "Barang \"{$label}\" belum terdaftar di stok gudang Anda. Tambahkan di Data Stok terlebih dahulu.",
            ];
        }

        return ['ok' => true];
    }

    public static function approve(PeminjamanPpe $peminjaman, \Illuminate\Support\Collection $subBarangMap, \Illuminate\Support\Collection $varianMap): void
    {
        if (! $peminjaman->isPending()) {
            throw new \RuntimeException('Peminjaman tidak dalam status menunggu approval.');
        }

        $stokSumber = self::findStok(
            $peminjaman->idgudang_sumber,
            $peminjaman->idsubbarang,
            $peminjaman->idbarangvarian
        );

        if (! $stokSumber) {
            $label = self::labelForItem($peminjaman->idsubbarang, $peminjaman->idbarangvarian, $subBarangMap, $varianMap);
            throw new \RuntimeException("Barang \"{$label}\" tidak ditemukan di stok gudang sumber.");
        }

        if ($peminjaman->qty > $stokSumber->qty) {
            $label = self::labelForItem($peminjaman->idsubbarang, $peminjaman->idbarangvarian, $subBarangMap, $varianMap);
            throw new \RuntimeException("Stok \"{$label}\" di gudang sumber tidak cukup (tersedia: {$stokSumber->qty}).");
        }

        $stokPeminjam = self::findStok(
            $peminjaman->idgudang_peminjam,
            $peminjaman->idsubbarang,
            $peminjaman->idbarangvarian
        );

        if (! $stokPeminjam) {
            $label = self::labelForItem($peminjaman->idsubbarang, $peminjaman->idbarangvarian, $subBarangMap, $varianMap);
            throw new \RuntimeException("Barang \"{$label}\" belum terdaftar di stok gudang peminjam.");
        }

        DB::transaction(function () use ($peminjaman, $stokSumber, $stokPeminjam) {
            $stokSumber = Stok::where('id', $stokSumber->id)->lockForUpdate()->firstOrFail();
            $stokPeminjam = Stok::where('id', $stokPeminjam->id)->lockForUpdate()->firstOrFail();

            if ($peminjaman->qty > $stokSumber->qty) {
                throw new \RuntimeException('Stok gudang sumber tidak cukup.');
            }

            $stokSumber->decrement('qty', $peminjaman->qty);
            $stokPeminjam->increment('qty', $peminjaman->qty);

            $peminjaman->update([
                'status'           => PeminjamanPpe::STATUS_APPROVED,
                'tanggal_diterima' => now()->toDateString(),
            ]);
        });
    }

    public static function reject(PeminjamanPpe $peminjaman, string $catatanTolak): void
    {
        if (! $peminjaman->isPending()) {
            throw new \RuntimeException('Peminjaman tidak dalam status menunggu approval.');
        }

        $peminjaman->update([
            'status'          => PeminjamanPpe::STATUS_REJECTED,
            'catatan_tolak'   => $catatanTolak,
            'tanggal_ditolak' => now()->toDateString(),
        ]);
    }

    public static function kembalikan(PeminjamanPpe $peminjaman, \Illuminate\Support\Collection $subBarangMap, \Illuminate\Support\Collection $varianMap): void
    {
        if (! $peminjaman->isApproved()) {
            throw new \RuntimeException('Hanya peminjaman yang sudah disetujui yang bisa dikembalikan.');
        }

        $stokPeminjam = self::findStok(
            $peminjaman->idgudang_peminjam,
            $peminjaman->idsubbarang,
            $peminjaman->idbarangvarian
        );

        if (! $stokPeminjam || $peminjaman->qty > $stokPeminjam->qty) {
            $label = self::labelForItem($peminjaman->idsubbarang, $peminjaman->idbarangvarian, $subBarangMap, $varianMap);
            throw new \RuntimeException("Stok \"{$label}\" di gudang peminjam tidak cukup untuk dikembalikan.");
        }

        $stokSumber = self::findStok(
            $peminjaman->idgudang_sumber,
            $peminjaman->idsubbarang,
            $peminjaman->idbarangvarian
        );

        if (! $stokSumber) {
            $label = self::labelForItem($peminjaman->idsubbarang, $peminjaman->idbarangvarian, $subBarangMap, $varianMap);
            throw new \RuntimeException("Barang \"{$label}\" tidak ditemukan di stok gudang sumber.");
        }

        DB::transaction(function () use ($peminjaman, $stokPeminjam, $stokSumber) {
            $stokPeminjam = Stok::where('id', $stokPeminjam->id)->lockForUpdate()->firstOrFail();
            $stokSumber = Stok::where('id', $stokSumber->id)->lockForUpdate()->firstOrFail();

            if ($peminjaman->qty > $stokPeminjam->qty) {
                throw new \RuntimeException('Stok gudang peminjam tidak cukup.');
            }

            $stokPeminjam->decrement('qty', $peminjaman->qty);
            $stokSumber->increment('qty', $peminjaman->qty);

            $peminjaman->update([
                'status'                  => PeminjamanPpe::STATUS_RETURNED,
                'tanggal_dikembalikan'    => now()->toDateString(),
            ]);
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
        \Illuminate\Support\Collection $subBarangMap,
        \Illuminate\Support\Collection $varianMap
    ): string {
        if ($idbarangvarian && $varianMap->has($idbarangvarian)) {
            return $varianMap[$idbarangvarian]['label'];
        }

        if ($idsubbarang && $subBarangMap->has($idsubbarang)) {
            return $subBarangMap[$idsubbarang]['label'];
        }

        return $idbarangvarian ? 'Varian #'.$idbarangvarian : 'Sub Barang #'.$idsubbarang;
    }
}
