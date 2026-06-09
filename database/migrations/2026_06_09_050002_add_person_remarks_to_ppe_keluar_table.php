<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppe_keluar', function (Blueprint $table) {
            // idpersonel (dari API) supaya kepemilikan item melekat ke ORANG, lintas gudang.
            $table->unsignedInteger('idpersonel')->nullable()->after('idgudang');
            $table->text('catatan')->nullable()->after('tanggal');
        });

        // Backfill idpersonel dari tabel personel untuk data lama.
        DB::statement('
            UPDATE ppe_keluar pk
            JOIN personel p ON p.id = pk.personel_id
            SET pk.idpersonel = p.idpersonel
            WHERE pk.idpersonel IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('ppe_keluar', function (Blueprint $table) {
            $table->dropColumn(['idpersonel', 'catatan']);
        });
    }
};
