<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppe_keluar', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('idgudang');
            $table->unsignedInteger('idsubbarang');
            $table->integer('qty');
            $table->date('tanggal');
            $table->foreignId('personel_id')->constrained('personel')->cascadeOnDelete();
            $table->foreignId('mobilisasi_id')->nullable()->constrained('mobilisasi')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppe_keluar');
    }
};
