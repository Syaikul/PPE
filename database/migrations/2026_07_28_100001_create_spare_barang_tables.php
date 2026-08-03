<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spare_barang', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('idgudang');
            $table->string('no_sr');
            $table->foreignId('personel_id')->nullable()->constrained('personel')->nullOnDelete();
            $table->date('tanggal');
            $table->timestamps();
        });

        Schema::create('spare_barang_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_barang_id')->constrained('spare_barang')->cascadeOnDelete();
            $table->unsignedInteger('idsubbarang')->nullable();
            $table->unsignedInteger('idbarangvarian')->nullable();
            $table->integer('jumlah');
            $table->integer('sisa');
            $table->date('returned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('spare_barang_pemakaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_barang_item_id')->constrained('spare_barang_item')->cascadeOnDelete();
            $table->foreignId('personel_id')->constrained('personel')->cascadeOnDelete();
            $table->integer('qty')->default(1);
            $table->string('status', 20)->default('menunggu');
            $table->text('catatan')->nullable();
            $table->text('approval_catatan')->nullable();
            $table->date('tanggal');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_barang_pemakaian');
        Schema::dropIfExists('spare_barang_item');
        Schema::dropIfExists('spare_barang');
    }
};
