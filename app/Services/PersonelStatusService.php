<?php

namespace App\Services;

use App\Models\MobilisasiPersonel;
use App\Models\Personel;

class PersonelStatusService
{
    /** Personel sedang dalam mobilisasi aktif (draft/berjalan, belum demob). */
    public static function isActivelyMobilized(int $idpersonel): bool
    {
        return MobilisasiPersonel::query()
            ->whereNull('demob_status')
            ->whereHas('mobilisasi', fn ($q) => $q->whereIn('status', ['draft', 'berjalan']))
            ->whereHas('personel', fn ($q) => $q->where('idpersonel', $idpersonel))
            ->exists();
    }

    /** Demob sudah dimulai tapi belum di-approve (lintas gudang). */
    public static function hasPendingDemob(int $idpersonel): bool
    {
        return MobilisasiPersonel::query()
            ->whereIn('demob_status', [
                MobilisasiPersonel::DEMOB_BELUM_CEK,
                MobilisasiPersonel::DEMOB_MENUNGGU,
            ])
            ->whereHas('personel', fn ($q) => $q->where('idpersonel', $idpersonel))
            ->exists();
    }

    public static function currentStatus(int $idpersonel): string
    {
        return self::isActivelyMobilized($idpersonel) ? 'Onshore' : 'Offshore';
    }

    /** Set Onshore di semua gudang untuk idpersonel yang sama. */
    public static function syncOnshore(int $idpersonel): void
    {
        Personel::where('idpersonel', $idpersonel)->update(['status' => 'Onshore']);
    }

    /** Set Offshore di semua gudang jika tidak ada mobilisasi aktif. */
    public static function syncOffshore(int $idpersonel): void
    {
        if (self::isActivelyMobilized($idpersonel)) {
            return;
        }

        Personel::where('idpersonel', $idpersonel)->update(['status' => 'Offshore']);
    }

    /** Perbaiki status semua personel dari state mobilisasi saat ini. */
    public static function resyncAll(): void
    {
        Personel::query()
            ->select('idpersonel')
            ->distinct()
            ->pluck('idpersonel')
            ->each(function (int $idpersonel) {
                Personel::where('idpersonel', $idpersonel)
                    ->update(['status' => self::currentStatus($idpersonel)]);
            });
    }
}
