<?php

namespace App\Services;

use App\Models\MobilisasiPersonel;
use App\Models\Personel;

class PersonelStatusService
{
    public const STATUS_ONSITE = 'Onsite';
    public const STATUS_OFFSITE = 'Offsite';

    /** Personel sedang dalam mobilisasi aktif (draft/berjalan, belum demob). */
    public static function isActivelyMobilized(int $idpersonel): bool
    {
        return self::activeMobilisasiQuery($idpersonel)->exists();
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
        return self::isActivelyMobilized($idpersonel) ? self::STATUS_ONSITE : self::STATUS_OFFSITE;
    }

    /** idgudang asal mobilisasi aktif personel (null bila tidak sedang Onsite). */
    public static function activeMobilisasiGudang(int $idpersonel): ?int
    {
        $mp = self::activeMobilisasiQuery($idpersonel)
            ->with('mobilisasi:id,idgudang')
            ->latest('id')
            ->first();

        return $mp?->mobilisasi ? (int) $mp->mobilisasi->idgudang : null;
    }

    /** Set Onsite di semua gudang untuk idpersonel yang sama. */
    public static function syncOnsite(int $idpersonel): void
    {
        Personel::where('idpersonel', $idpersonel)->update(['status' => self::STATUS_ONSITE]);
    }

    /** Set Offsite di semua gudang jika tidak ada mobilisasi aktif. */
    public static function syncOffsite(int $idpersonel): void
    {
        if (self::isActivelyMobilized($idpersonel)) {
            return;
        }

        Personel::where('idpersonel', $idpersonel)->update(['status' => self::STATUS_OFFSITE]);
    }

    /** Perbaiki status satu orang dari state mobilisasi saat ini. */
    public static function resyncOne(int $idpersonel): void
    {
        Personel::where('idpersonel', $idpersonel)
            ->update(['status' => self::currentStatus($idpersonel)]);
    }

    /** Perbaiki status semua personel dari state mobilisasi saat ini. */
    public static function resyncAll(): void
    {
        Personel::query()
            ->select('idpersonel')
            ->distinct()
            ->pluck('idpersonel')
            ->each(fn (int $idpersonel) => self::resyncOne($idpersonel));
    }

    private static function activeMobilisasiQuery(int $idpersonel)
    {
        return MobilisasiPersonel::query()
            ->whereNull('demob_status')
            ->whereHas('mobilisasi', fn ($q) => $q->whereIn('status', ['draft', 'berjalan']))
            ->whereHas('personel', fn ($q) => $q->where('idpersonel', $idpersonel));
    }
}
