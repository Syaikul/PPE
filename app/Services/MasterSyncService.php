<?php

namespace App\Services;

use App\Models\MasterData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Sync manual data master dari API ke database lokal.
 *
 * Setelah sync, seluruh aplikasi membaca salinan lokal sehingga tetap jalan
 * walaupun API master mati atau tidak ada internet.
 */
class MasterSyncService
{
    /** Endpoint API => label untuk ditampilkan di UI. */
    public const ENDPOINTS = [
        'gudang'             => 'Gudang',
        'personel'           => 'Personel',
        'posisi'             => 'Posisi',
        'barang-with-varian' => 'Barang & Varian',
        'posisippe'          => 'Perlengkapan per Posisi',
    ];

    public static function baseUrl(): string
    {
        return rtrim(config('services.master_api.url', 'http://127.0.0.1:8000'), '/');
    }

    /**
     * Ambil satu endpoint dari API master.
     *
     * @return array{ok: bool, rows: array<int, mixed>, error: ?string}
     */
    public static function fetchRemote(string $endpoint): array
    {
        try {
            $response = Http::timeout((int) config('services.master_api.timeout', 30))
                ->get(self::baseUrl().'/api/'.ltrim($endpoint, '/'));
        } catch (\Throwable $e) {
            return ['ok' => false, 'rows' => [], 'error' => 'Tidak bisa terhubung ke API master: '.$e->getMessage()];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'rows' => [], 'error' => 'API master membalas status '.$response->status().'.'];
        }

        $rows = $response->json('data');

        if (! is_array($rows)) {
            return ['ok' => false, 'rows' => [], 'error' => 'Format respons API tidak dikenali (field "data" kosong).'];
        }

        return ['ok' => true, 'rows' => $rows, 'error' => null];
    }

    /**
     * Sync semua endpoint. Data lama hanya ditimpa bila pengambilan berhasil,
     * sehingga sync gagal tidak pernah mengosongkan data yang sudah ada.
     *
     * @return array<int, array{endpoint: string, label: string, ok: bool, jumlah: int, error: ?string}>
     */
    public static function syncAll(): array
    {
        $hasil = [];

        foreach (self::ENDPOINTS as $endpoint => $label) {
            $hasil[] = self::syncOne($endpoint, $label);
        }

        return $hasil;
    }

    /** @return array{endpoint: string, label: string, ok: bool, jumlah: int, error: ?string} */
    public static function syncOne(string $endpoint, ?string $label = null): array
    {
        $label ??= self::ENDPOINTS[$endpoint] ?? $endpoint;
        $remote = self::fetchRemote($endpoint);

        if (! $remote['ok']) {
            return [
                'endpoint' => $endpoint,
                'label'    => $label,
                'ok'       => false,
                'jumlah'   => self::count($endpoint),
                'error'    => $remote['error'],
            ];
        }

        $rows = $remote['rows'];

        DB::transaction(function () use ($endpoint, $rows) {
            MasterData::updateOrCreate(
                ['endpoint' => $endpoint],
                [
                    'payload'   => json_encode($rows, JSON_UNESCAPED_UNICODE),
                    'jumlah'    => count($rows),
                    'synced_at' => now(),
                ]
            );
        });

        MasterApiService::flushMemo();

        return [
            'endpoint' => $endpoint,
            'label'    => $label,
            'ok'       => true,
            'jumlah'   => count($rows),
            'error'    => null,
        ];
    }

    /** Ringkasan status tiap endpoint untuk halaman Sync. */
    public static function status(): array
    {
        $tersimpan = MasterData::all()->keyBy('endpoint');

        $status = [];

        foreach (self::ENDPOINTS as $endpoint => $label) {
            $row = $tersimpan->get($endpoint);

            $status[] = [
                'endpoint'  => $endpoint,
                'label'     => $label,
                'jumlah'    => $row?->jumlah ?? 0,
                'synced_at' => $row?->synced_at,
                'ada'       => $row !== null,
            ];
        }

        return $status;
    }

    private static function count(string $endpoint): int
    {
        return (int) MasterData::where('endpoint', $endpoint)->value('jumlah');
    }
}
