<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppe_keluar', function (Blueprint $table) {
            $table->unsignedInteger('idbarangvarian')->nullable()->after('idsubbarang');
        });

        Schema::table('mobilisasi_pengecekan', function (Blueprint $table) {
            $table->unsignedInteger('idbarangvarian')->nullable()->after('idsubbarang');
        });
    }

    public function down(): void
    {
        Schema::table('ppe_keluar', function (Blueprint $table) {
            $table->dropColumn('idbarangvarian');
        });

        Schema::table('mobilisasi_pengecekan', function (Blueprint $table) {
            $table->dropColumn('idbarangvarian');
        });
    }
};
