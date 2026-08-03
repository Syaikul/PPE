<?php

namespace App\Services;

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
        $gudang = MasterApiService::gudangById($idgudang);

        return $gudang['namagudang'] ?? 'Gudang #'.$idgudang;
    }
}
