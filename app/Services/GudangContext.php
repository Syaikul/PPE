<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GudangContext
{
    public static function activate(int $idgudang): void
    {
        session([
            'idgudang'   => $idgudang,
            'namagudang' => self::resolveName($idgudang),
        ]);
    }

    public static function ensureNamagudangInSession(): void
    {
        $idgudang = session('idgudang');
        if (! $idgudang) {
            return;
        }

        if (session('namagudang') && (int) session('_gudang_ctx_id') === (int) $idgudang) {
            return;
        }

        session([
            'namagudang'     => self::resolveName((int) $idgudang),
            '_gudang_ctx_id' => (int) $idgudang,
        ]);
    }

    public static function titlePrefix(): string
    {
        return session('namagudang') ?: 'Workshop';
    }

    private static function resolveName(int $idgudang): string
    {
        $gudang = self::fetchGudang($idgudang);

        return $gudang['namagudang'] ?? 'Gudang #'.$idgudang;
    }

    private static function fetchGudang(int $idgudang): ?array
    {
        $response = Http::get('http://127.0.0.1:8000/api/gudang');
        $list = $response->successful() ? ($response->json('data') ?? []) : [];

        return collect($list)->firstWhere('idgudang', $idgudang);
    }
}
