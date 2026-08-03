<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Satu pintu akses ke API master data (gudang, personel, barang, dll).
 * Ubah URL di .env: MASTER_API_URL=http://127.0.0.1:8000
 */
class MasterApiService
{
    public static function baseUrl(): string
    {
        return rtrim(config('services.master_api.url', 'http://127.0.0.1:8000'), '/');
    }

    /** @return array<int, array<string, mixed>> */
    public static function get(string $endpoint): array
    {
        $response = Http::get(self::baseUrl().'/api/'.ltrim($endpoint, '/'));

        return $response->successful() ? ($response->json('data') ?? []) : [];
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
