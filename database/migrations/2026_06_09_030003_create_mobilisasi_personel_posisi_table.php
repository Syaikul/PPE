<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobilisasi_personel_posisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobilisasi_personel_id')->constrained('mobilisasi_personel')->cascadeOnDelete();
            $table->unsignedInteger('idposisi');
            $table->timestamps();

            $table->unique(['mobilisasi_personel_id', 'idposisi'], 'mob_pers_posisi_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobilisasi_personel_posisi');
    }
};
