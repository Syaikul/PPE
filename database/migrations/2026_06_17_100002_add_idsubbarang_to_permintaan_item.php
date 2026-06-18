<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_item', function (Blueprint $table) {
            $table->unsignedInteger('idsubbarang')->nullable()->after('permintaan_id');
        });

        Schema::table('permintaan_item', function (Blueprint $table) {
            $table->unsignedInteger('idbarangvarian')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('permintaan_item', function (Blueprint $table) {
            $table->unsignedInteger('idbarangvarian')->nullable(false)->change();
            $table->dropColumn('idsubbarang');
        });
    }
};
