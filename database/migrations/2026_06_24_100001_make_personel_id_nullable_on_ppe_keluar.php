<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppe_keluar', function (Blueprint $table) {
            $table->dropForeign(['personel_id']);
        });

        Schema::table('ppe_keluar', function (Blueprint $table) {
            $table->unsignedBigInteger('personel_id')->nullable()->change();
            $table->foreign('personel_id')->references('id')->on('personel')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ppe_keluar', function (Blueprint $table) {
            $table->dropForeign(['personel_id']);
        });

        Schema::table('ppe_keluar', function (Blueprint $table) {
            $table->unsignedBigInteger('personel_id')->nullable(false)->change();
            $table->foreign('personel_id')->references('id')->on('personel')->cascadeOnDelete();
        });
    }
};
