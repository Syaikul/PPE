<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stok', function (Blueprint $table) {
            $table->unsignedInteger('idsubbarang')->nullable()->after('idgudang');
        });

        Schema::table('stok', function (Blueprint $table) {
            $table->unsignedInteger('idbarangvarian')->nullable()->change();
        });

        Schema::table('stok', function (Blueprint $table) {
            $table->dropUnique('stok_gudang_varian_unique');
        });
    }

    public function down(): void
    {
        Schema::table('stok', function (Blueprint $table) {
            $table->unique(['idgudang', 'idbarangvarian'], 'stok_gudang_varian_unique');
        });

        Schema::table('stok', function (Blueprint $table) {
            $table->unsignedInteger('idbarangvarian')->nullable(false)->change();
            $table->dropColumn('idsubbarang');
        });
    }
};
