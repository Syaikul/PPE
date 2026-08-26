<?php

namespace App\Services;

use App\Models\User;

class AccessControl
{
    public const SUPERADMIN = 'superadmin';

    public const ADMIN_PPE = 'admin_ppe';

    public const HSE_OFFICER = 'hse_officer';

    public const MANAGER = 'manager';

    public const LEVEL_NONE = 'none';

    public const LEVEL_VIEW = 'view';

    public const LEVEL_CRUD = 'crud';

    /** @return array<string, string> */
    public static function roles(): array
    {
        return [
            self::SUPERADMIN  => 'SuperAdmin',
            self::ADMIN_PPE   => 'AdminPPE',
            self::HSE_OFFICER => 'HSE Officer',
            self::MANAGER     => 'Manager',
        ];
    }

    public static function roleLabel(?string $role): string
    {
        return self::roles()[$role] ?? ($role ?? '-');
    }

    /**
     * Modul => role => level (none/view/crud).
     *
     * @return array<string, array<string, string>>
     */
    public static function matrix(): array
    {
        $allView = [
            self::SUPERADMIN  => self::LEVEL_VIEW,
            self::ADMIN_PPE   => self::LEVEL_VIEW,
            self::HSE_OFFICER => self::LEVEL_VIEW,
            self::MANAGER     => self::LEVEL_VIEW,
        ];

        $adminCrud = [
            self::SUPERADMIN  => self::LEVEL_CRUD,
            self::ADMIN_PPE   => self::LEVEL_CRUD,
            self::HSE_OFFICER => self::LEVEL_VIEW,
            self::MANAGER     => self::LEVEL_VIEW,
        ];

        $adminOnly = [
            self::SUPERADMIN  => self::LEVEL_CRUD,
            self::ADMIN_PPE   => self::LEVEL_CRUD,
            self::HSE_OFFICER => self::LEVEL_NONE,
            self::MANAGER     => self::LEVEL_NONE,
        ];

        return [
            'dashboard'        => $allView,
            'stok'             => $adminCrud,
            'ppe_masuk'        => $adminCrud,
            'ppe_keluar'       => $adminCrud,
            'transfer'         => $adminCrud,
            'personel'         => $adminCrud,
            'pemakaian_ppe'    => $allView,
            'permintaan_buat'  => $adminOnly,
            'permintaan'       => $adminCrud,
            'approval_demob'   => [
                self::SUPERADMIN  => self::LEVEL_CRUD,
                self::ADMIN_PPE   => self::LEVEL_NONE,
                self::HSE_OFFICER => self::LEVEL_CRUD,
                self::MANAGER     => self::LEVEL_NONE,
            ],
            'mobilisasi'       => $adminCrud,
            'demobilisasi'     => $adminCrud,
            'peminjaman'       => [
                self::SUPERADMIN  => self::LEVEL_CRUD,
                self::ADMIN_PPE   => self::LEVEL_VIEW,
                self::HSE_OFFICER => self::LEVEL_CRUD,
                self::MANAGER     => self::LEVEL_VIEW,
            ],
            'master_sync'      => [
                self::SUPERADMIN  => self::LEVEL_CRUD,
                self::ADMIN_PPE   => self::LEVEL_NONE,
                self::HSE_OFFICER => self::LEVEL_NONE,
                self::MANAGER     => self::LEVEL_NONE,
            ],
            'users'            => [
                self::SUPERADMIN  => self::LEVEL_CRUD,
                self::ADMIN_PPE   => self::LEVEL_NONE,
                self::HSE_OFFICER => self::LEVEL_NONE,
                self::MANAGER     => self::LEVEL_NONE,
            ],
        ];
    }

    /**
     * Nama rute => [modul, level minimum].
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function routeMap(): array
    {
        return [
            'dashboard'                          => ['dashboard', self::LEVEL_VIEW],
            'dashboard.notifications.dismiss'    => ['dashboard', self::LEVEL_VIEW],

            'gudang.stok'                        => ['stok', self::LEVEL_VIEW],
            'gudang.stok.store'                  => ['stok', self::LEVEL_CRUD],
            'gudang.stok.update'                 => ['stok', self::LEVEL_CRUD],
            'gudang.stok.destroy'                => ['stok', self::LEVEL_CRUD],

            'gudang.transfer-barang'             => ['transfer', self::LEVEL_VIEW],
            'gudang.transfer-barang.store'       => ['transfer', self::LEVEL_CRUD],

            'gudang.ppe-masuk'                   => ['ppe_masuk', self::LEVEL_VIEW],
            'gudang.ppe-keluar'                  => ['ppe_keluar', self::LEVEL_VIEW],

            'gudang.personel'                    => ['personel', self::LEVEL_VIEW],
            'gudang.personel.store'              => ['personel', self::LEVEL_CRUD],
            'gudang.personel.update'             => ['personel', self::LEVEL_CRUD],
            'gudang.personel.destroy'            => ['personel', self::LEVEL_CRUD],

            'gudang.pemakaian-ppe'               => ['pemakaian_ppe', self::LEVEL_VIEW],
            'gudang.pemakaian-ppe.show'          => ['pemakaian_ppe', self::LEVEL_VIEW],

            'gudang.permintaan-ppe.create'       => ['permintaan_buat', self::LEVEL_CRUD],
            'gudang.permintaan-ppe.export'       => ['permintaan_buat', self::LEVEL_CRUD],

            'gudang.permintaan'                  => ['permintaan', self::LEVEL_VIEW],
            'gudang.permintaan.show'             => ['permintaan', self::LEVEL_VIEW],
            'gudang.permintaan.pdf'              => ['permintaan', self::LEVEL_VIEW],
            'gudang.permintaan.store'            => ['permintaan', self::LEVEL_CRUD],
            'gudang.permintaan.update'           => ['permintaan', self::LEVEL_CRUD],
            'gudang.permintaan.destroy'          => ['permintaan', self::LEVEL_CRUD],
            'gudang.permintaan.kedatangan'       => ['permintaan', self::LEVEL_CRUD],

            'gudang.mobilisasi'                  => ['mobilisasi', self::LEVEL_VIEW],
            'gudang.mobilisasi.show'             => ['mobilisasi', self::LEVEL_VIEW],
            'gudang.mobilisasi.perlengkapan'     => ['mobilisasi', self::LEVEL_VIEW],
            'gudang.mobilisasi.create'           => ['mobilisasi', self::LEVEL_CRUD],
            'gudang.mobilisasi.store'            => ['mobilisasi', self::LEVEL_CRUD],
            'gudang.mobilisasi.destroy'          => ['mobilisasi', self::LEVEL_CRUD],
            'gudang.mobilisasi.perlengkapan.store' => ['mobilisasi', self::LEVEL_CRUD],
            'gudang.mobilisasi.perlengkapan.update' => ['mobilisasi', self::LEVEL_CRUD],
            'gudang.mobilisasi.perlengkapan.destroy' => ['mobilisasi', self::LEVEL_CRUD],
            'gudang.mobilisasi.spare.store'      => ['mobilisasi', self::LEVEL_CRUD],
            'gudang.mobilisasi.spare.pakai'      => ['mobilisasi', self::LEVEL_CRUD],
            'gudang.mobilisasi.spare.kembalikan' => ['mobilisasi', self::LEVEL_CRUD],
            'gudang.mobilisasi.pengecekan'       => ['mobilisasi', self::LEVEL_CRUD],
            'gudang.mobilisasi.pengecekan.update' => ['mobilisasi', self::LEVEL_CRUD],
            'gudang.mobilisasi.pengecekan.submit' => ['mobilisasi', self::LEVEL_CRUD],
            'gudang.mobilisasi.jalankan'         => ['mobilisasi', self::LEVEL_CRUD],

            'gudang.demobilisasi'                => ['demobilisasi', self::LEVEL_VIEW],
            'gudang.demobilisasi.dokumen-mob'    => ['demobilisasi', self::LEVEL_VIEW],
            'gudang.demobilisasi.dokumen-demob'  => ['demobilisasi', self::LEVEL_VIEW],
            'gudang.demobilisasi.selesaikan'     => ['demobilisasi', self::LEVEL_CRUD],
            'gudang.demobilisasi.cek'            => ['demobilisasi', self::LEVEL_CRUD],
            'gudang.demobilisasi.cek.store'      => ['demobilisasi', self::LEVEL_CRUD],

            'gudang.approval-demob'              => ['approval_demob', self::LEVEL_CRUD],
            'gudang.approval-demob.approve'      => ['approval_demob', self::LEVEL_CRUD],
            'gudang.approval-demob.reject'       => ['approval_demob', self::LEVEL_CRUD],
            'gudang.approval-demob.spare.approve' => ['approval_demob', self::LEVEL_CRUD],
            'gudang.approval-demob.spare.reject' => ['approval_demob', self::LEVEL_CRUD],

            'gudang.peminjaman-ppe'              => ['peminjaman', self::LEVEL_VIEW],
            'gudang.peminjaman-ppe.store'        => ['peminjaman', self::LEVEL_CRUD],
            'gudang.peminjaman-ppe.approve'      => ['peminjaman', self::LEVEL_CRUD],
            'gudang.peminjaman-ppe.reject'       => ['peminjaman', self::LEVEL_CRUD],
            'gudang.peminjaman-ppe.kembalikan'   => ['peminjaman', self::LEVEL_CRUD],

            'master.sync'                        => ['master_sync', self::LEVEL_CRUD],
            'master.sync.run'                    => ['master_sync', self::LEVEL_CRUD],

            'users.index'                        => ['users', self::LEVEL_CRUD],
            'users.store'                        => ['users', self::LEVEL_CRUD],
            'users.update'                       => ['users', self::LEVEL_CRUD],
            'users.destroy'                      => ['users', self::LEVEL_CRUD],
        ];
    }

    public static function levelFor(User $user, string $module): string
    {
        return self::matrix()[$module][$user->role] ?? self::LEVEL_NONE;
    }

    public static function allows(User $user, string $module, string $needed): bool
    {
        if ($user->role === self::SUPERADMIN) {
            return true;
        }

        $have = self::levelFor($user, $module);

        if ($needed === self::LEVEL_VIEW) {
            return in_array($have, [self::LEVEL_VIEW, self::LEVEL_CRUD], true);
        }

        return $have === self::LEVEL_CRUD;
    }

    public static function requirementFor(?string $routeName): ?array
    {
        if (! $routeName) {
            return null;
        }

        return self::routeMap()[$routeName] ?? null;
    }
}
