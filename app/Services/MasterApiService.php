<?php

namespace App\Services;

use App\Models\MasterData;

/**
 * Satu pintu akses ke data master (gudang, personel, barang, dll).
 *
 * Data dibaca dari salinan lokal di tabel `master_data`, bukan dari API.
 * Isi salinan itu lewat sync manual: `php artisan master:sync` atau menu
 * "Sync Data Master" di aplikasi. Ubah URL API di .env: MASTER_API_URL=...
 */
class MasterApiService
{
    /** Cache per-request supaya satu endpoint hanya di-decode sekali. */
    private static array $memo = [];

    public static function baseUrl(): string
    {
        return MasterSyncService::baseUrl();
    }

    public static function flushMemo(): void
    {
        self::$memo = [];
    }

    /** @return array<int, array<string, mixed>> */
    public static function get(string $endpoint): array
    {
        $endpoint = ltrim($endpoint, '/');

        if (array_key_exists($endpoint, self::$memo)) {
            return self::$memo[$endpoint];
        }

        $row = MasterData::where('endpoint', $endpoint)->first();

        if ($row) {
            return self::$memo[$endpoint] = $row->rows();
        }

        // Belum pernah sync: ambil langsung dari API sekali supaya aplikasi tetap
        // bisa dipakai, tapi hasilnya tidak disimpan (sync tetap harus manual).
        if (config('services.master_api.fallback', true)) {
            $remote = MasterSyncService::fetchRemote($endpoint);

            return self::$memo[$endpoint] = ($remote['ok'] ? $remote['rows'] : []);
        }

        return self::$memo[$endpoint] = [];
    }

    /** @return array<int, array<string, mixed>> */
    public static function gudang(): array
    {
        return self::get('gudang');
    }

    public static function gudangById(int $idgudang): ?array
    {
        return collect(self::gudang())->firstWhere('idgudang', $idgudang);
    }

    /** Gudang tujuan transfer barang (lihat config/transfer.php). */
    public static function gudangTransferTujuan(): ?array
    {
        $keyword = strtoupper((string) config('transfer.gudang_tujuan_nama', 'Workshop'));

        return collect(self::gudang())->first(function (array $g) use ($keyword) {
            $nama = strtoupper((string) ($g['namagudang'] ?? ''));

            return $nama === $keyword || str_contains($nama, $keyword);
        });
    }

    public static function gudangTransferTujuanLabel(): string
    {
        $gudang = self::gudangTransferTujuan();

        return $gudang['namagudang'] ?? (string) config('transfer.gudang_tujuan_nama', 'Workshop');
    }

    public static function isGudangTransferTujuan(int $idgudang): bool
    {
        $gudang = self::gudangTransferTujuan();

        return $gudang && (int) $gudang['idgudang'] === $idgudang;
    }

    /** @return array<int, array<string, mixed>> */
    public static function personel(): array
    {
        return self::get('personel');
    }

    /** @return array<int, array<string, mixed>> */
    public static function posisi(): array
    {
        return self::get('posisi');
    }

    /** @return array<int, array<string, mixed>> */
    public static function barangWithVarian(): array
    {
        return self::get('barang-with-varian');
    }

    /** @return array<int, array<string, mixed>> */
    public static function posisiPpe(): array
    {
        return self::get('posisippe');
    }
}
