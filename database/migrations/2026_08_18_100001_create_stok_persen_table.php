<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Persentase Min-Max per sub barang per gudang (basis: jumlah personel di gudang).
        Schema::create('stok_persen', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('idgudang');
            $table->unsignedInteger('idsubbarang');
            $table->decimal('persen', 5, 2)->default(10);
            $table->timestamps();

            $table->unique(['idgudang', 'idsubbarang'], 'stok_persen_gudang_sub_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_persen');
    }
};
