<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobilisasi_pengecekan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobilisasi_personel_id')->constrained('mobilisasi_personel')->cascadeOnDelete();
            $table->unsignedInteger('idsubbarang');
            $table->integer('jumlah')->default(1);
            $table->string('status')->default('tidak'); // ada | tidak
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['mobilisasi_personel_id', 'idsubbarang'], 'mob_pengecekan_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobilisasi_pengecekan');
    }
};
