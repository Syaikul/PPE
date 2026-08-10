<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Salinan lokal data master (gudang, personel, posisi, barang, posisippe).
        // Diisi lewat sync manual sehingga aplikasi tidak perlu memanggil API tiap request.
        Schema::create('master_data', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint')->unique();
            $table->longText('payload');
            $table->unsignedInteger('jumlah')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_data');
    }
};
