<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman_ppe', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('idgudang_peminjam');
            $table->unsignedInteger('idgudang_sumber');
            $table->unsignedInteger('idsubbarang')->nullable();
            $table->unsignedInteger('idbarangvarian')->nullable();
            $table->integer('qty');
            $table->text('catatan')->nullable();
            $table->text('catatan_tolak')->nullable();
            $table->string('status', 20)->default('pending');
            $table->date('tanggal_pengajuan');
            $table->date('tanggal_diterima')->nullable();
            $table->date('tanggal_ditolak')->nullable();
            $table->date('tanggal_dikembalikan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman_ppe');
    }
};
