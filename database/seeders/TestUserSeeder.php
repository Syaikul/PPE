<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserGudang;
use App\Services\AccessControl;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun uji otomatis (TestSprite / PHPUnit).
 * Password semua role selain SuperAdmin: password123
 */
class TestUserSeeder extends Seeder
{
    /** Gudang ONWJ — akun scoped hanya punya akses ke gudang ini. */
    public const GUDANG_A = 1;

    /** Gudang OSES Nuri — dipakai untuk uji IDOR (akses ditolak). */
    public const GUDANG_B = 2;

    public const PASSWORD = 'password123';

    public function run(): void
    {
        $accounts = [
            [
                'name'       => 'SuperAdmin Test',
                'email'      => 'test@example.com',
                'password'   => 'admin',
                'role'       => AccessControl::SUPERADMIN,
                'all_gudang' => true,
                'gudang'     => [],
            ],
            [
                'name'       => 'AdminPPE Test',
                'email'      => 'adminppe@example.com',
                'password'   => self::PASSWORD,
                'role'       => AccessControl::ADMIN_PPE,
                'all_gudang' => false,
                'gudang'     => [self::GUDANG_A],
            ],
            [
                'name'       => 'HSE Officer Test',
                'email'      => 'hse@example.com',
                'password'   => self::PASSWORD,
                'role'       => AccessControl::HSE_OFFICER,
                'all_gudang' => false,
                'gudang'     => [self::GUDANG_A],
            ],
            [
                'name'       => 'Manager Test',
                'email'      => 'manager@example.com',
                'password'   => self::PASSWORD,
                'role'       => AccessControl::MANAGER,
                'all_gudang' => false,
                'gudang'     => [self::GUDANG_A],
            ],
            [
                'name'       => 'AdminPPE Gudang B',
                'email'      => 'adminppe2@example.com',
                'password'   => self::PASSWORD,
                'role'       => AccessControl::ADMIN_PPE,
                'all_gudang' => false,
                'gudang'     => [self::GUDANG_B],
            ],
        ];

        foreach ($accounts as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'       => $data['name'],
                    'password'   => Hash::make($data['password']),
                    'role'       => $data['role'],
                    'is_active'  => true,
                    'all_gudang' => $data['all_gudang'],
                ]
            );

            UserGudang::where('user_id', $user->id)->delete();

            if (! $data['all_gudang']) {
                foreach ($data['gudang'] as $idgudang) {
                    UserGudang::create([
                        'user_id'  => $user->id,
                        'idgudang' => $idgudang,
                    ]);
                }
            }
        }
    }
}
